@extends('layouts.app')

@section('styles')
@endsection

@section('content')
<!-- Section : Hero -->
<section class="container " id="home" >

<div class="modal fade" id="qrisModal" tabindex="-1" aria-labelledby="qrisModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="qrisModalLabel">QRIS</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
      <h6 class="text-uppercase"><span class="bg-black text-white px-1">Send to Admin After Completing Payment</span></h6>
      <img src="{{ asset('QRIS.png') }}" alt="QRIS" class="img-fluid rounded">
        <a href="http://wa.me/62{{ $data_web->wa ?? '' }}" target="_blank" class="btn mt-2 btn-black border">
            <i class="bi bi-whatsapp me-2"></i>
            Whatsapp
        </a>
      </div>
    </div>
  </div>
</div>


        <div class="row align-items-center">
            <div class="col-lg-6 order-2 order-lg-1">
                <h6 class="text-uppercase"><span class="bg-black text-white px-1">Speed. Style. Story.</span></h6>
                <h1><strong>{{ $data_web->first_name ?? '' }}</strong>{{ $data_web->last_name ?? '' }}</h1>
                <p>Captures the thrill, emotion, and detail in every photograph.</p>
                <div>
                    <a href="http://wa.me/62{{ $data_web->wa ?? '' }}" target="_blank" class="btn btn-black border">
                        <i class="bi bi-whatsapp me-2"></i>
                        Whatsapp
                    </a>
                    <button type="button" class="btn border text-uppercase fw-semibold" data-bs-toggle="modal" data-bs-target="#qrisModal">
                        <i class="bi bi-currency-dollar me-1"></i>
                        Pay Here / QRIS
                    </button>


                </div>
                
                <div class="mt-3">
                    <small>FOLLOW US:</small>
                    @if (count($data_links) > 0)
                        @foreach ($data_links as $item)
                            <a href="{{ $item['url'] }}" target="_blank" class="text-black ms-2">{{ $item['label'] }}</a>
                        @endforeach
                    @endif
                </div>
            </div>
            
        </div>
    </section>
    <!-- Section: Products -->
    <section class="container py-5">
        <div class="row" id="product-cards">

        @foreach ($data_konten as $item)
            <div class="col-12 col-md-3 mb-4">
                <a href="{{ $item->link }}" target="_blank" class="text-decoration-none text-black">
                    <div class="{{ $loop->first ? 'product-card-static' : 'product-card' }}">
                        <div class="image-container">
                            <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->title }}" />
                            <div class="overlay-info">
                                <h5 class="fw-bold mb-1 text-white">{{ $item->title }}</h5>
                                @if (!$item->is_price_enabled)
                                    <p class="mb-0 text-white">FREE DOWNLOAD</p>
                                @else
                                    <p class="mb-0 text-white">
                                        Rp {{ number_format($item->price, 0, ',', '.') }} / PHOTO
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach


        </div>
    </section>
@endsection
@section('scripts')

@endsection
