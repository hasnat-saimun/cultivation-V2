
                    <div class="header-logo">
                        @php
                            $assetPath = static function (?string $path): string {
                                $path = ltrim((string) $path, '/');
                                $path = preg_replace('#^public/#', '', $path) ?? $path;

                                return asset($path);
                            };
                        @endphp
                        <a href="{{ route('academicPart') }}"><img src="{{ $assetPath('back-office/img/logo1.png') }}" class="logosize" alt="logo"></a>
                    </div>