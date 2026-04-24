@extends('layouts.app')
@section('content')
    <div class='card'>
        <h1>{{ $item->id ? 'Edit' : 'Create' }} {{ $entityName }}</h1>
        
        @sppform('student')
        @sppbind($item)
        @sppform_start('student_form')
            <div>
                <label>Name</label>
                @sppelement('name')
            </div>
            <div>
                <label>Description</label>
                @sppelement('description')
            </div>
            <div>
                @sppelement('submit')
            </div>
        @sppform_end
        
        <a href='?'>Back to List</a>
    </div>
@endsection