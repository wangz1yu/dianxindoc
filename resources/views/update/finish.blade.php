@extends('update.update-layout')

@section('content')
<div class="sm:mx-auto sm:w-full sm:max-w-md">
    @if (file_exists(public_path('logo.png')))
    <div class="text-center">
        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" style="width: 50%;height: auto;border-radius: 10px;" class="mx-auto h-12 w-auto">
    </div>
    @endif
</div>

<div class="panel text-center">
    <h3 class="install-completed-header">Update has been successfully completed!</h3>

    <p class="mt-2">Your application will reload shortly to apply the latest updates.</p>

    <p id="countdown" class="text-gray-500 text-sm mt-1">Reloading in 3 seconds...</p>

    <a href="/" rel="noopener noreferrer"
        class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 hover:bg-primary-700 mt-4">
        Close Update Page
    </a>
</div>

<script>
    // Clear JWT + session storage
    localStorage.clear();
    sessionStorage.clear();

    // Countdown + reload
    let seconds = 3;
    const countdown = document.getElementById('countdown');
    const interval = setInterval(() => {
        seconds--;
        countdown.textContent = `Reloading in ${seconds} seconds...`;
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.replace("/"); // force reload to home
        }
    }, 1000);
</script>
@endsection