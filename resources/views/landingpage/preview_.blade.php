@extends('layouts.app')

@section('styles')
@endsection

@section('content')
    <button class="btn btn-primary">Tarik Data</button>
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('.btn-primary').on('click', function() {
                Swal.fire({
                    title: 'Enter Data',
                    input: 'text',
                    inputPlaceholder: 'Enter your input',
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    preConfirm: (inputValue) => {
                        if (!inputValue) {
                            Swal.showValidationMessage('Input cannot be empty')
                        } else {
                            Swal.fire({
                                title: 'Processing...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading()
                                }
                            });

                            return $.ajax({
                                type: 'POST',
                                url: '{{ route('drive.download-data') }}',
                                data: {
                                    folder_id: inputValue,
                                    _token: '{{ csrf_token() }}'
                                },
                                success: (response) => {
                                    if (response.status === 'success') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: response.message,
                                        });
                                    }
                                },
                                error: (error) => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Something went wrong!',
                                    });
                                }
                            });
                        }
                    }
                });

            });
        })
    </script>
@endsection
