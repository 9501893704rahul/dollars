{{-- resources/views/properties/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 max-w-full overflow-hidden">
            <div class="flex-1 min-w-0 max-w-full">
                <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight break-words">
                    Edit Property
                </h2>
                <p class="mt-1 text-xs sm:text-sm text-gray-500 break-words">
                    Update this property. Latitude &amp; longitude will be updated automatically from the address if
                    left empty.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0 w-full sm:w-auto">
                <x-button variant="secondary" href="{{ route('properties.index') }}" class="w-full sm:w-auto text-center whitespace-nowrap">
                    Back to List
                </x-button>
                <x-button variant="secondary" href="{{ route('properties.rooms.index', $property) }}" class="w-full sm:w-auto text-center whitespace-nowrap">
                    Rooms
                </x-button>
                <x-button variant="secondary" href="{{ route('properties.property-tasks.index', $property) }}" class="w-full sm:w-auto text-center whitespace-nowrap">
                    <span class="hidden sm:inline">Property Tasks</span>
                    <span class="sm:hidden">Tasks</span>
                </x-button>
            </div>
        </div>
    </x-slot>

    <x-card class="max-w-full">
        <form x-data="propertyEditForm()" x-init="init()" method="post" action="{{ route('properties.update', $property) }}"
            enctype="multipart/form-data" @submit.prevent="handleSubmit($event)" class="max-w-full">
            @csrf
            @method('PUT')

            {{-- If the current user is an owner (not admin), lock owner_id --}}
            @if (!($isAdmin ?? false) && auth()->user()->hasRole('owner'))
                <input type="hidden" name="owner_id" value="{{ auth()->id() }}">
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 max-w-full">
                {{-- Left column: Image (current + replace/remove) --}}
                <div class="lg:col-span-1 max-w-full overflow-hidden">
                    <x-form.label value="Property Photo" />
                    <div class="mt-1 border-2 border-dashed dark:border-gray-700 rounded-xl sm:rounded-2xl p-3 sm:p-4 text-center bg-gray-50/40 dark:bg-gray-800/40 max-w-full overflow-hidden">
                        @php
                            $photoUrl = method_exists($property, 'getPhotoUrlAttribute')
                                ? $property->photo_url
                                : ($property->photo_path
                                    ? (Str::startsWith($property->photo_path, ['http://', 'https://'])
                                        ? $property->photo_path
                                        : asset('storage/' . $property->photo_path))
                                    : asset('images/placeholders/property.png'));
                        @endphp

                        <template x-if="!previewUrl">
                            <img src="{{ $photoUrl }}" alt="Current photo"
                                class="rounded-xl object-cover h-48 w-full shadow-sm max-w-full" />
                        </template>

                        <template x-if="previewUrl">
                            <div class="w-full max-w-full overflow-hidden">
                                <img :src="previewUrl" alt="Preview"
                                    class="rounded-xl object-cover h-48 w-full shadow-sm max-w-full" />
                                <template x-if="previewUrl && !hasFileSelected">
                                    <div class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800 max-w-full overflow-hidden">
                                        <p class="font-semibold break-words">⚠️ Image preview restored</p>
                                        <p class="mt-1 break-words">Please click "Choose File" to re-select the image file. Browser security requires this after a page refresh.</p>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <input type="file" name="photo" class="hidden" x-ref="file" @change="preview($event)"
                            accept="image/*" />

                        <div class="mt-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 sm:gap-3 max-w-full overflow-hidden">
                            <x-button type="button" variant="secondary" @click="$refs.file.click()" class="w-full sm:w-auto whitespace-nowrap">
                                Choose File
                            </x-button>

                            @if ($property->photo_path)
                                <label class="inline-flex items-center justify-center gap-2 text-sm text-gray-600 cursor-pointer break-words">
                                    <x-form.checkbox name="remove_photo" value="1" />
                                    <span class="break-words">Remove photo</span>
                                </label>
                            @endif
                        </div>

                        @error('photo')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Right column: Fields --}}
                <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 max-w-full overflow-visible">
                    {{-- Admin-only owner select --}}
                    @role('admin')
                        <div class="md:col-span-2">
                            <x-form.label value="Owner" />
                            <x-form.select name="owner_id" class="w-full">
                                @foreach ($owners ?? [] as $id => $name)
                                    <option value="{{ $id }}" @selected(old('owner_id', $property->owner_id) == $id)>{{ $name }}
                                    </option>
                                @endforeach
                            </x-form.select>
                            <x-form.error :messages="$errors->get('owner_id')" />
                        </div>
                    @endrole

                    {{-- Name --}}
                    <div class="md:col-span-2">
                        <x-form.label value="Name" />
                        <x-form.input name="name" class="w-full" required :value="old('name', $property->name)"
                            placeholder="e.g. Seaside Apartment 3B" />
                        <x-form.error :messages="$errors->get('name')" />
                    </div>

                    {{-- Address --}}
                    <div class="md:col-span-2 relative">
                        <x-form.label value="Address (optional)" />
                        <x-form.input
                            name="address"
                            id="address-autocomplete"
                            class="w-full"
                            x-model="address"
                            @input="handleAddressInput()"
                            @focus="showSuggestions = true"
                            @blur="setTimeout(() => showSuggestions = false, 200)"
                            :value="old('address', $property->address)"
                            placeholder="Start typing address..."
                            autocomplete="off"
                        />

                        {{-- Address Autocomplete Dropdown --}}
                        <div
                            x-show="showSuggestions && suggestions.length > 0"
                            x-cloak
                            class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        >
                            <template x-for="(suggestion, index) in suggestions" :key="index">
                                <button
                                    type="button"
                                    class="w-full px-4 py-3 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0 flex items-start gap-3"
                                    @click="selectSuggestion(suggestion)"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100" x-text="suggestion.display_name || suggestion.structured_formatting?.main_text || suggestion.description"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="suggestion.type || suggestion.structured_formatting?.secondary_text || ''"></p>
                                    </div>
                                </button>
                            </template>
                        </div>

                        <template x-if="isLoadingSuggestions">
                            <div class="absolute right-3 top-9">
                                <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </template>

                        <div class="mt-1 space-y-1">
                            <p class="text-xs text-gray-500 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
                                </svg>
                                <span>
                                    If you provide an address and leave Latitude/Longitude empty,
                                    we’ll auto-fill them for you.
                                </span>
                            </p>

                            <p class="text-xs text-gray-500">
                                You do <span class="font-semibold">not</span> need to fetch latitude/longitude manually.
                                They’re updated automatically when you save.
                            </p>

                            <template x-if="isGeocoding">
                                <p class="text-xs text-indigo-600 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 3a9 9 0 019 9m-9 9a9 9 0 01-9-9" />
                                    </svg>
                                    Fetching coordinates&hellip; please wait.
                                </p>
                            </template>

                            <template x-if="geocodeError">
                                <p class="text-xs text-red-600" x-text="geocodeError"></p>
                            </template>
                        </div>

                        <x-form.error :messages="$errors->get('address')" />
                    </div>

                    {{-- Latitude --}}
                    <div>
                        <x-form.label value="Latitude (optional)" />
                        <x-form.input name="latitude" class="w-full" x-model="latitude" :value="old('latitude', $property->latitude)"
                            inputmode="decimal" pattern="^[0-9]*\\.?[0-9]*$"
                            x-on:input="
                                $event.target.value = ($event.target.value || '')
                                    .replace(/[^0-9.]/g, '')
                                    .replace(/(\\..*)\\./g, '$1');
                                latitude = $event.target.value;
                            "
                            placeholder="Auto-filled from address" />
                        <x-form.error :messages="$errors->get('latitude')" />
                    </div>

                    {{-- Longitude --}}
                    <div>
                        <x-form.label value="Longitude (optional)" />
                        <x-form.input name="longitude" class="w-full" x-model="longitude" :value="old('longitude', $property->longitude)"
                            inputmode="decimal" pattern="^[0-9]*\\.?[0-9]*$"
                            x-on:input="
                                $event.target.value = ($event.target.value || '')
                                    .replace(/[^0-9.]/g, '')
                                    .replace(/(\\..*)\\./g, '$1');
                                longitude = $event.target.value;
                            "
                            placeholder="Auto-filled from address" />
                        <x-form.error :messages="$errors->get('longitude')" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 mt-6 sm:mt-8 sm:justify-end max-w-full">
                <x-button x-bind:disabled="isGeocoding"
                    x-bind:class="isGeocoding ? 'opacity-60 cursor-not-allowed' : ''" class="w-full sm:w-auto whitespace-nowrap">
                    <span x-show="!isGeocoding">Update</span>
                    <span x-show="isGeocoding">Fetching coordinates…</span>
                </x-button>

                <x-button variant="secondary" href="{{ route('properties.index') }}" class="w-full sm:w-auto whitespace-nowrap">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>

    {{-- Conditionally load Google Places API if using Google provider --}}
    @if(config('services.geocoding.provider') === 'google')
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places"></script>
    @endif

    {{-- Alpine helpers (kept as before; do not modify photo uploader behavior/UI) --}}
    <script>
        function propertyEditForm() {
            return {
                address: @json(old('address', $property->address)),
                latitude: @json(old('latitude', $property->latitude)),
                longitude: @json(old('longitude', $property->longitude)),
                previewUrl: null,
                hasFileSelected: false, // Track if file input has a file

                // UX state
                isGeocoding: false,
                geocodeError: '',
                isSubmitting: false, // Flag to prevent re-triggering submit handler

                // Geocoding provider: 'openstreetmap' or 'google'
                geocodingProvider: @json(config('services.geocoding.provider', 'openstreetmap')),

                // Address Autocomplete
                suggestions: [],
                showSuggestions: false,
                isLoadingSuggestions: false,
                autocompleteService: null,
                placesService: null,
                searchTimeout: null,

                init() {
                    // Restore preview from sessionStorage if it exists (e.g., after form error)
                    const savedPreview = sessionStorage.getItem('property_photo_preview_edit');
                    if (savedPreview) {
                        this.previewUrl = savedPreview;
                        this.hasFileSelected = false; // File input is cleared by browser on refresh
                    }

                    // Check if file input has a file (in case it was preserved somehow)
                    this.$nextTick(() => {
                        if (this.$refs.file && this.$refs.file.files.length > 0) {
                            this.hasFileSelected = true;
                            // If file is actually present, update preview from it
                            const file = this.$refs.file.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    this.previewUrl = e.target.result;
                                    sessionStorage.setItem('property_photo_preview_edit', e.target.result);
                                };
                                reader.readAsDataURL(file);
                            }
                        }
                    });

                    // Only clear sessionStorage if form loads without any errors AND no file is selected
                    // This means it was a successful submission (page redirected back fresh)
                    @if($errors->isEmpty())
                        // Small delay to ensure file check completes first
                        setTimeout(() => {
                            if (!this.hasFileSelected) {
                                sessionStorage.removeItem('property_photo_preview_edit');
                            }
                        }, 100);
                    @endif

                    // Initialize Google Places Autocomplete (only if using Google provider)
                    if (this.geocodingProvider === 'google' && typeof google !== 'undefined' && google.maps && google.maps.places) {
                        this.autocompleteService = new google.maps.places.AutocompleteService();
                        // Create a dummy div for PlacesService (required)
                        const mapDiv = document.createElement('div');
                        this.placesService = new google.maps.places.PlacesService(mapDiv);
                    }
                },

                handleAddressInput() {
                    clearTimeout(this.searchTimeout);

                    if (!this.address || this.address.length < 3) {
                        this.suggestions = [];
                        return;
                    }

                    this.searchTimeout = setTimeout(() => {
                        this.fetchPlaceSuggestions();
                    }, 300);
                },

                async fetchPlaceSuggestions() {
                    this.isLoadingSuggestions = true;

                    // Use OpenStreetMap Nominatim for autocomplete
                    if (this.geocodingProvider === 'openstreetmap') {
                        try {
                            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(this.address)}&format=json&addressdetails=1&limit=5`;
                            const res = await fetch(url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'User-Agent': 'RoomReady/1.0'
                                }
                            });

                            if (!res.ok) throw new Error(`HTTP ${res.status}`);

                            const data = await res.json();
                            this.isLoadingSuggestions = false;

                            if (data && data.length > 0) {
                                this.suggestions = data.map(item => ({
                                    display_name: item.display_name,
                                    lat: item.lat,
                                    lon: item.lon,
                                    type: item.type ? item.type.replace(/_/g, ' ') : ''
                                }));
                                this.showSuggestions = true;
                            } else {
                                this.suggestions = [];
                            }
                        } catch (error) {
                            console.warn('OpenStreetMap autocomplete error:', error);
                            this.isLoadingSuggestions = false;
                            this.suggestions = [];
                        }
                        return;
                    }

                    // Use Google Places for autocomplete
                    if (this.autocompleteService) {
                        try {
                            const request = {
                                input: this.address,
                                types: ['address']
                            };

                            this.autocompleteService.getPlacePredictions(request, (predictions, status) => {
                                this.isLoadingSuggestions = false;

                                if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
                                    this.suggestions = predictions;
                                    this.showSuggestions = true;
                                } else {
                                    this.suggestions = [];
                                }
                            });
                        } catch (error) {
                            console.warn('Google Places autocomplete error:', error);
                            this.isLoadingSuggestions = false;
                            this.suggestions = [];
                        }
                    } else {
                        this.isLoadingSuggestions = false;
                    }
                },

                selectSuggestion(suggestion) {
                    this.showSuggestions = false;
                    this.suggestions = [];

                    // Handle OpenStreetMap suggestion
                    if (suggestion.display_name) {
                        this.address = suggestion.display_name;
                        this.latitude = parseFloat(suggestion.lat);
                        this.longitude = parseFloat(suggestion.lon);
                        return;
                    }

                    // Handle Google Places suggestion
                    this.address = suggestion.description;
                    if (this.placesService && suggestion.place_id) {
                        this.isGeocoding = true;
                        this.placesService.getDetails(
                            { placeId: suggestion.place_id, fields: ['geometry'] },
                            (place, status) => {
                                this.isGeocoding = false;
                                if (status === google.maps.places.PlacesServiceStatus.OK && place.geometry) {
                                    this.latitude = place.geometry.location.lat();
                                    this.longitude = place.geometry.location.lng();
                                }
                            }
                        );
                    }
                },

                preview(event) {
                    const file = event.target.files?.[0];
                    if (!file) {
                        this.hasFileSelected = false;
                        return;
                    }

                    this.hasFileSelected = true;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previewUrl = e.target.result;
                        // Save to sessionStorage to preserve on page refresh
                        sessionStorage.setItem('property_photo_preview_edit', e.target.result);
                    };
                    reader.readAsDataURL(file);
                },

                async handleSubmit(event) {
                    // If we're already submitting (after geocoding attempt), let it proceed
                    if (this.isSubmitting) {
                        return;
                    }

                    this.geocodeError = '';

                    if (this.isGeocoding) {
                        event.preventDefault();
                        return;
                    }

                    const needsGeocode = this.address && (!this.latitude || !this.longitude);

                    if (needsGeocode) {
                        event.preventDefault();
                        await this.geocodeIfNeeded(true); // force on submit

                        // If geocoding fails, show a warning but allow submission to continue
                        // since coordinates are optional
                        if (this.address && (!this.latitude || !this.longitude)) {
                            this.geocodeError =
                                'Could not find coordinates for this address. You can continue without them or enter them manually.';
                        }

                        // Set flag and submit the form directly
                        // Don't clear sessionStorage here - let it persist in case of backend errors
                        this.isSubmitting = true;
                        event.target.submit();
                        return;
                    }

                    // Don't clear sessionStorage here - it will be cleared in init() if submission was successful
                    // This way, if there's a backend validation error, the preview will be restored
                },

                async geocodeIfNeeded(force = false) {
                    if (!this.address) return;

                    // Skip if not forced and both coords already exist
                    if (!force && this.latitude && this.longitude) return;

                    this.isGeocoding = true;
                    this.geocodeError = '';

                    // Use OpenStreetMap Nominatim for geocoding
                    if (this.geocodingProvider === 'openstreetmap') {
                        try {
                            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(this.address)}&format=json&limit=1`;
                            const res = await fetch(url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'User-Agent': 'RoomReady/1.0'
                                }
                            });

                            if (!res.ok) throw new Error(`HTTP ${res.status}`);

                            const data = await res.json();

                            if (data && data.length > 0) {
                                this.latitude = parseFloat(data[0].lat);
                                this.longitude = parseFloat(data[0].lon);
                            } else {
                                this.geocodeError = 'No coordinates found for this address. You can still enter them manually.';
                            }
                        } catch (error) {
                            console.warn('Geocoding failed', error);
                            this.geocodeError = 'Unable to fetch coordinates right now. Please try again or enter them manually.';
                        } finally {
                            this.isGeocoding = false;
                        }
                        return;
                    }

                    // Use Google Geocoding API
                    try {
                        const apiKey = @json(config('services.google.geocoding_api_key'));
                        if (!apiKey) {
                            throw new Error('Google Geocoding API key is not configured');
                        }

                        const url =
                            `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(this.address)}&key=${apiKey}`;
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) {
                            throw new Error(`HTTP ${res.status}`);
                        }

                        const data = await res.json();

                        if (data.status === 'OK' && data.results && data.results.length > 0) {
                            const location = data.results[0].geometry.location;
                            this.latitude = location.lat;
                            this.longitude = location.lng;
                        } else if (data.status === 'ZERO_RESULTS') {
                            this.geocodeError =
                                'No coordinates found for this address. You can still enter them manually.';
                        } else {
                            throw new Error(`Geocoding API error: ${data.status}`);
                        }
                    } catch (error) {
                        console.warn('Geocoding failed', error);
                        this.geocodeError =
                            'Unable to fetch coordinates right now. Please try again or enter them manually.';
                    } finally {
                        this.isGeocoding = false;
                    }
                },
            }
        }
    </script>

    {{-- Assigned Rooms + Property Tasks (AJAX) --}}
    <div class="mt-6">
        <div
            x-data="propertyAssignmentsPanel({
                roomsUrl: @js(route('api.properties.assigned-rooms', $property)),
                tasksUrl: @js(route('api.properties.assigned-property-tasks', $property)),
            })"
            x-init="init()"
        >
            <x-card class="max-w-full">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Assigned Items</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Loaded via API/AJAX — use the buttons to manage assignments.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <x-button type="button" variant="secondary" @click="refreshAll()" class="w-full sm:w-auto whitespace-nowrap">
                            Refresh
                        </x-button>
                        <x-button variant="secondary" href="{{ route('properties.rooms.index', $property) }}" class="w-full sm:w-auto whitespace-nowrap">
                            Manage Rooms
                        </x-button>
                        <x-button variant="secondary" href="{{ route('properties.property-tasks.index', $property) }}" class="w-full sm:w-auto whitespace-nowrap">
                            Manage Property Tasks
                        </x-button>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    {{-- Rooms --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-gray-50/40 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200">Rooms</h4>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-300 dark:border-indigo-500/20"
                                    x-text="rooms.length"></span>
                            </div>
                            <template x-if="loadingRooms">
                                <span class="text-xs text-indigo-600 dark:text-indigo-300">Loading…</span>
                            </template>
                        </div>

                        <template x-if="roomsError">
                            <p class="mt-2 text-xs text-red-600" x-text="roomsError"></p>
                        </template>

                        <template x-if="!loadingRooms && !roomsError && rooms.length === 0">
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No rooms assigned yet.</p>
                        </template>

                        <ul class="mt-3 space-y-2" x-show="rooms.length > 0">
                            <template x-for="room in rooms" :key="room.id">
                                <li class="flex items-center justify-between gap-3 p-2 rounded-lg bg-white dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="room.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="'Tasks: ' + (room.tasks_count ?? 0)"></span>
                                            <span class="mx-1">•</span>
                                            <span x-text="'Order: ' + (room.sort_order ?? 0)"></span>
                                        </p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- Property Tasks --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-gray-50/40 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200">Property Tasks</h4>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-300 dark:border-indigo-500/20"
                                    x-text="tasks.length"></span>
                            </div>
                            <template x-if="loadingTasks">
                                <span class="text-xs text-indigo-600 dark:text-indigo-300">Loading…</span>
                            </template>
                        </div>

                        <template x-if="tasksError">
                            <p class="mt-2 text-xs text-red-600" x-text="tasksError"></p>
                        </template>

                        <template x-if="!loadingTasks && !tasksError && tasks.length === 0">
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No property tasks assigned yet.</p>
                        </template>

                        <ul class="mt-3 space-y-2" x-show="tasks.length > 0">
                            <template x-for="task in tasks" :key="task.id">
                                <li class="flex items-center justify-between gap-3 p-2 rounded-lg bg-white dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="task.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="phaseLabel(task.phase) || '—'"></span>
                                            <span class="mx-1">•</span>
                                            <span x-text="'Order: ' + (task.sort_order ?? 0)"></span>
                                        </p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
