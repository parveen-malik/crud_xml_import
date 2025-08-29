<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Services\ImportContactsFromXml;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // public function index()
    public function index(Request $request)
    {
        // $list_data=Contact::orderByDesc('id')->get();
        // return view('contact',compact('list_data'));

        $q = $request->string('q');
        $list_data = Contact::when($q, fn($qq) =>
                $qq->where('name','like',"%{$q}%")->orWhere('phone','like',"%{$q}%"))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('contact', compact('list_data','q'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
        return view('contact');
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    // public function store(StoreContactRequest $request)
    {
        $Contact= new Contact;
        $Contact->create([
            'name'=>$request->name,
            'phone'=>$request->phone
        ]);
        return redirect()->route('Contact.index')->with('ok','Contact created.');

        // Contact::create($request->validated());
        // return redirect()->route('Contact.index')->with('ok','Contact created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $edit_data=Contact::where('id',$id)->first();
        return view('contact',compact('edit_data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    // public function update(UpdateContactRequest $request, Contact $contact)
    {
        $Contact=Contact::where('id',$id)->first();
        $Contact->update([
            'name'=>$request->name,
            'phone'=>$request->phone,
        ]);
        return redirect()->route('Contact.index')->with('ok','Contact updated.');

        // $contact->update($request->validated());
        // return redirect()->route('Contact.index')->with('ok','Contact updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Contact=Contact::where('id',$id)->delete();
        return redirect()->route('Contact.index')->with('ok','Contact deleted.');
    }

    public function importForm()
    {
        return view('contacts_import');
    }

    public function import(Request $request, ImportContactsFromXml $service)
    {
        $request->validate([
            'xml_file' => ['required','file','mimetypes:text/xml,application/xml','max:20480'] // 20 MB
        ]);

        // Store file
        $path = $request->file('xml_file')->storeAs(
            'imports',
            uniqid().'_contacts.xml',
            'public'
        );

        return redirect()->route('Contact.import.progress', ['path' => 'public/'.$path]);
    }

    public function progress(Request $request, ImportContactsFromXml $service)
    {
        $path = $request->input('path');
        $absPath = storage_path('app/' . ltrim($path, '/'));

        if (!file_exists($absPath)) {
            return redirect()->route('Contact.index')
                ->with('ok', "Import file missing at: $absPath");
        }

        $key = 'import_progress_' . md5($path);

        if (!Cache::has($key)) {
            Cache::put($key, [
                'processed' => 0,
                'total'     => 0,
                'inserted'  => 0,
                'updated'   => 0,
                'skipped'   => 0,
                'done'      => false,
                'msg'       => 'Starting...'
            ], 600);

            // Run immediately (no queue worker needed)
            $report = $service->handleWithProgress($absPath, $key);
            Cache::put($key, $report + ['done' => true], 600);
        }

        $progress = Cache::get($key);

        if ($progress['done'] ?? false) {
            if (file_exists($absPath)) {
                unlink($absPath);
            }

            return redirect()->route('Contact.index')->with('ok',
                "Imported {$progress['inserted']} inserted, {$progress['updated']} updated, {$progress['skipped']} skipped."
            );
        }

        return view('contact_progress', [
            'progress' => $progress,
            'path'     => $path
        ]);
    }

}
