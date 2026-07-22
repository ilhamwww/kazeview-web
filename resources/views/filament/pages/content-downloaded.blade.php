<x-filament-panels::page>
    <div class="flex justify-end items-center gap-3">
        <x-filament::button id="refreshBtn" color="success" icon="heroicon-o-arrow-path"
            class="px-5 py-2 rounded-xl shadow-md hover:scale-105 transition transform duration-200"
            onclick="location.reload()">
            Refresh Halaman
        </x-filament::button>

        <x-filament::button id="downloadBtn" color="warning" icon="heroicon-o-arrow-down-tray"
            class="px-5 py-2 rounded-xl shadow-md hover:scale-105 transition transform duration-200">
            Download Data
        </x-filament::button>
    </div>


    {{ $this->table }}

    <x-filament::modal id="filesModal" width="4xl">
        <x-slot name="header">
            <h2 class="text-lg font-bold">Downloaded Files</h2>
        </x-slot>

        <div>
            <table class="min-w-full text-sm border">
                <thead>
                    <tr>
                        <th class="border px-3 py-2">Nomor</th>
                        <th class="border px-3 py-2">File Name</th>
                        <th class="border px-3 py-2">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Ambil & urutkan file berdasarkan angka terakhir sebelum ekstensi (DSC05289.jpg => 05289)
                        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();

                        $filesList = \App\Models\ListDownloaded::where('id_download', $this->selectedDownloadId)->orderByRaw("
                                COALESCE(
                                    CAST(substring(split_part(file_name, '.', 1) FROM '[0-9]+$') AS INT),
                                    0
                                ) ASC, file_name ASC
                            ")->get();

                        $totalFiles = 0;
                        $total_download = 0;

                        if ($this->selectedFolderId) {
                            $folderPath = 'downloads/' . $this->selectedFolderId;
                            $files = \Illuminate\Support\Facades\Storage::disk('public')->files($folderPath);
                            $totalFiles = count($files);
                        }

                        if ($this->selectedDownloadId) {
                            $downloadData = \Illuminate\Support\Facades\DB::table('download_data')
                                ->where('id', $this->selectedDownloadId)
                                ->first();

                            $total_download = $downloadData ? $downloadData->total_files : 0;
                        }
                    @endphp


                    <div class="mt-4">
                        <p>Total Downloads: {{ $totalFiles }}</p>
                    </div>

                    <div class="mt-4">
                        <p>Total Files: {{ $total_download }}</p>
                    </div>

                    @if ($filesList->count() > 0)
                        @foreach ($filesList as $file)
                            <tr>
                                <td class="border px-3 py-2">{{ $loop->iteration }}</td>
                                <td class="border px-3 py-2">{{ $file->file_name }}</td>
                                <td class="border px-3 py-2">{{ $file->created_at }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3" class="border px-3 py-2 text-center">Data Kosong</td>
                        </tr>
                    @endif


                </tbody>
            </table>
        </div>
    </x-filament::modal>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const url = window.location.href;
            const lastSegment = url.split('/').pop();
            console.log(lastSegment);



            document.getElementById('downloadBtn').addEventListener('click', function() {
                Swal.fire({
                    title: 'Enter Data',
                    input: 'text',
                    inputPlaceholder: 'Enter your input',
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    preConfirm: (inputValue) => {
                        if (!inputValue) {
                            Swal.showValidationMessage('Input cannot be empty');
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: "Processing...",
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                            });
                            return fetch("{{ route('drive.download-data') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify({
                                        folder_id: inputValue,
                                        id_content: lastSegment
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: data.message,
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: data.message,
                                            showConfirmButton: false,
                                            timer: 1500,
                                            timerProgressBar: true,
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Something went wrong!',
                                    });
                                });
                        }
                    }
                });
            });
        });
    </script>
</x-filament-panels::page>
