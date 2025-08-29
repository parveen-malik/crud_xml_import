@extends('layout')
@section('title','Import Contacts (XML)')
@section('content')
<form method="post" action="{{ route('Contact.import') }}" enctype="multipart/form-data" class="space-y-3">
  @csrf
  <div>
    

     <label for="xml_file">Upload Contacts XML:</label>
    <input type="file" name="xml_file" accept=".xml" class="border px-3 py-2 rounded w-full"  required>

    @error('file')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
  </div>
  <p class="text-sm text-gray-600">
    Expected structure: &lt;contacts&gt;&lt;contact&gt;&lt;name&gt;...&lt;/name&gt;&lt;phone&gt;...&lt;/phone&gt;&lt;/contact&gt;...&lt;/contacts&gt;.
  </p>
  <button class="px-4 py-2  rounded">Import</button> 
  <!-- bg-black text-white -->
  <a href="{{ route('Contact.index') }}" class="ml-2 underline">Back</a>
</form>
@endsection
