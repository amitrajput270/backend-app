@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Import Large File</h1>
    <form action="{{ url('import-large-file') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="file">Choose File:</label>
            <input type="file" class="form-control" id="file" name="file" required>
        </div><br>
        <button type="submit" class="btn btn-primary">Import</button>
    </form>
</div>
@endsection