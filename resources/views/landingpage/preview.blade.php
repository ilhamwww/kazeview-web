@extends('layouts.app')

@section('styles')

@endsection

@section('content')

<div class="container mt-5">
    <h2 class="mb-4">Galeri dari Google Drive</h2>
    <div class="row">
        @forelse($images as $image)
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="{{ $image['url'] }}" class="card-img-top" alt="{{ $image['name'] }}">
                    <div class="card-body">
                        <p class="card-text">{{ $image['name'] }}</p>
                    </div>
                </div>
            </div>
        @empty
            <p>Tidak ada gambar ditemukan di folder ini.</p>
        @endforelse
    </div>
</div>
<img src="https://drive.usercontent.google.com/download?id=1NbKPLFeXOSBow8N9GgFPKk-ALDYqK8Ds&export=view&authuser=0" alt="">
@endsection
@section('scripts')
<script>

</script>
@endsection
