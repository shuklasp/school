@extends('layouts.app')
@section('content')
    <div class='card'>
        <h1>{{ $title }}</h1>
        <table class='table'>
            <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>
                        <a href='?action=edit&id={{ $item->id }}'>Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <a href='?action=create' class='btn'>Add New</a>
    </div>
@endsection