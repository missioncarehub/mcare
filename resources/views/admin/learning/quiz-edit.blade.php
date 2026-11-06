@extends('admin.layouts.app', ['title' => 'Edit Quiz | MCARE Admin'])

@section('content')
    @include('trainer.quizzes.partials.form', [
        'formAction' => route('admin.learning.quizzes.update', $quiz),
        'formMethod' => 'PATCH',
        'pageTitle' => 'Edit quiz',
        'pageDescription' => 'Import questions from a file, or revise them before saving.',
        'submitLabel' => 'Save changes',
    ])
@endsection
