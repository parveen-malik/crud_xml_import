@extends('layout')
@section('title','Contacts')
@section('content')
	<a href="{{ route('Contact.create')}}" class="px-3 py-2 border rounded" > Add New</a>
	<div class="flex justify-between items-center mb-4">

	  <!-- <form method="get" class="flex gap-2">
	    <input name="q" value="{{-- $q --}}" placeholder="Search name or phone" class="border px-3 py-2 rounded w-64">
	    <button class="px-3 py-2 border rounded">Search</button>
	  </form> -->
	  <div class="flex gap-2">
	    <a href="{{ route('Contact.import.form') }}" class="px-3 py-2 border rounded">Import XML</a>

	    <a href="{{ route('Contact.create') }}" class="px-3 py-2 border rounded bg-black text-white">New</a>
	  </div>
	</div>
	@if(!empty($list_data))
		<h1>Contact list</h1>
		<table class="w-full bg-white border">
			<thead>
				<tr class="bg-gray-100">
				    <th class="text-left p-2 border">Id</th>
				    <th class="text-left p-2 border">Name</th>
				    <th class="text-left p-2 border">Phone</th>
				    <th class="p-2 border">Actions</th>
			  	</tr>
			</thead>
			<tbody>				
				@forelse($list_data as $row)
					<tr>
						<td class="p-2 border">{{ $row->id }}</td>
						<td class="p-2 border">{{ $row->name }}</td>
						<td class="p-2 border">{{ $row->phone }}</td>
						<td class="p-2 border text-center">
							<a href="{{ route('Contact.edit',$row->id) }}" class="underline">Edit</a>
							<form action="{{ route('Contact.destroy',$row->id) }}" method="post" class="inline">
								@method("delete")
								@csrf
								<button onclick="return confirm('Delete this contact?')" class="text-red-600 underline ml-2">Delete</button>
							</form>
						</td>
					</tr>
					@empty
			      	<tr><td colspan="3" class="p-4 text-center text-gray-500">No contacts yet.</td></tr>
			    @endforelse				
			</tbody>
		</table>
		<div class="mt-4">{{ $list_data->links() }}</div>
		@else
			<!-- <h2>Add Contact</h2> -->
			
				@if(isset($edit_data))
					@php
						$form_text="Edit Record";
						$form_action=route('Contact.update',$edit_data->id);
						$form_method= method_field('PUT');
						$button_text="Update Record";

						$name=$edit_data->name;
						$phone=$edit_data->phone;
					@endphp
				
				@else
					@php
						$form_text="Add Record";
						$form_action=route('Contact.store');
						$form_method= "";
						$button_text="Store Record";
						$form_method="";

						$name="";
						$phone="";
					@endphp
			@endif
			
			<form action="{{ $form_action }}" method="post" class="space-y-3">
				@csrf
				{{ $form_method }}
				<h3>{{ $form_text }}</h3>
				<div>
					<input type="text" name="name" value="{{ $name }}" placeholder="Enter Name" class="border px-3 py-2 rounded w-full" />
					@error('name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
				</div>
				<div>
					<input type="text" name="phone" value="{{ $phone }}" placeholder="Enter Contact number" class="border px-3 py-2 rounded w-full" />
					@error('phone')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
				</div>
				<button type="submit" name="submit"  class="px-3 py-2 border rounded">{{ $button_text }}</button>
				<a href="{{ route('Contact.index') }}" class="ml-2 underline">Cancel</a>
				
			</form>
	@endif
@endsection