<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ImportContactsFromXml
{
    public function handle(UploadedFile $file): array
    {
        $size = $file->getSize();
        if ($size <= 5 * 1024 * 1024) {
            return $this->importSmall($file);
        } elseif ($size <= 20 * 1024 * 1024) {
            return $this->importLarge($file);
        } else {
            return ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>['File too large (max 20 MB supported)']];
        }
    }

    private function importSmall(UploadedFile $file): array
    {
        $xmlString = file_get_contents($file->getRealPath());
        $xmlString = mb_convert_encoding($xmlString, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1254');
        $xml = simplexml_load_string($xmlString);
        if (! $xml) {
            return ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>['Invalid XML']];
        }

        $rows = [];
        foreach ($xml->contact as $c) {
            $name  = trim((string)($c->name ?? ''));
            $phone = trim((string)($c->phone ?? ''));
            if ($name === '' || $phone === '') continue;
            $rows[] = ['name'=>$name, 'phone'=>$this->normalizePhone($phone)];
        }
        return $this->bulkUpsert($rows);
    }

    private function importLarge(UploadedFile $file): array
    {
        $reader = new \XMLReader();
        $reader->open($file->getRealPath());

        $rows = []; $inserted=$updated=$skipped=0; $errors=[];
        DB::beginTransaction();
        try {
            while ($reader->read()) {
                if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name === 'contact') {
                    $node = simplexml_load_string($reader->readOuterXML());
                    $name = trim((string)($node->name ?? ''));
                    $phone= trim((string)($node->phone ?? ''));
                    if ($name === '' || $phone === '') { $skipped++; continue; }
                    $rows[] = ['name'=>$name, 'phone'=>$this->normalizePhone($phone)];
                    if (count($rows) >= 1000) {
                        $res = $this->bulkUpsert($rows);
                        $inserted+=$res['inserted']; $updated+=$res['updated']; $skipped+=$res['skipped'];
                        $rows=[];
                    }
                }
            }
            if ($rows) {
                $res = $this->bulkUpsert($rows);
                $inserted+=$res['inserted']; $updated+=$res['updated']; $skipped+=$res['skipped'];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[]=$e->getMessage();
        }
        $reader->close();
        return compact('inserted','updated','skipped','errors');
    }

    private function bulkUpsert(array $rows): array
    {
        $inserted=$updated=$skipped=0; $errors=[];
        try {
            $phones   = collect($rows)->pluck('phone')->all();
            $existing = Contact::whereIn('phone', $phones)->pluck('phone')->all();

            $insert = collect($rows)->reject(fn($r) => in_array($r['phone'], $existing))->values();
            $update = collect($rows)->filter(fn($r) => in_array($r['phone'], $existing))->values();

            $data = collect($rows)->map(fn($r)=>[
                'name'=>$r['name'],
                'phone'=>$r['phone'],
                'created_at'=>now(),
                'updated_at'=>now()
            ])->all();

            Contact::upsert($data,['phone'],['name','updated_at']);

            $inserted += $insert->count();
            $updated  += $update->count();

        } catch (\Throwable $e) {
            $errors[]=$e->getMessage();
        }
        return compact('inserted','updated','skipped','errors');
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/(?!^\+)[^\d]/', '', $phone);
        return preg_replace('/\s+/', '', $normalized);
    }

    public function handleWithProgress(string $absPath, string $cacheKey): array
    {
        $file = new \SplFileObject($absPath);
        $size = $file->getSize();
        if ($size <= 5*1024*1024) {
            return $this->importSmall(new \Illuminate\Http\UploadedFile($absPath, basename($absPath)));
        }
        elseif ($size <= 20*1024*1024) {
            return $this->importLargeWithProgress($absPath, $cacheKey);
        }
        return ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>['File too large']];
    }

    private function importLargeWithProgress(string $absPath, string $cacheKey): array
    {
        $reader = new \XMLReader();
        $reader->open($absPath);

        $rows=[]; $inserted=$updated=$skipped=0; $errors=[]; $count=0;
        $total=0;

        // Count contacts first
        while($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name==='contact') $total++;
        }
        $reader->close();
        $reader->open($absPath);

        DB::beginTransaction();
        try {
            while($reader->read()) {
                if ($reader->nodeType==\XMLReader::ELEMENT && $reader->name==='contact') {
                    $node = simplexml_load_string($reader->readOuterXML());
                    $name = trim((string)$node->name);
                    $phone= trim((string)$node->phone);
                    if ($name===''||$phone==='') { $skipped++; $count++; continue; }
                    $rows[]=['name'=>$name,'phone'=>$this->normalizePhone($phone)];
                    $count++;
                    if(count($rows)>=1000){
                        $res=$this->bulkUpsert($rows);
                        $inserted+=$res['inserted']; $updated+=$res['updated']; $skipped+=$res['skipped'];
                        $rows=[];
                    }
                    // update progress
                    Cache::put($cacheKey, [
                        'processed'=>$count,'total'=>$total,
                        'inserted'=>$inserted,'updated'=>$updated,'skipped'=>$skipped,
                        'done'=>false
                    ], 600);
                }
            }
            if($rows){
                $res=$this->bulkUpsert($rows);
                $inserted+=$res['inserted']; $updated+=$res['updated']; $skipped+=$res['skipped'];
            }
            DB::commit();
        }catch(\Throwable $e){
            DB::rollBack();
            $errors[]=$e->getMessage();
        }
        $reader->close();
        return compact('inserted','updated','skipped','errors');
    }
}
