{{-- resources/views/properties/create.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 max-w-full overflow-hidden">
      <div class="flex-1 min-w-0 max-w-full">
        <h2 class="font-semibold text-lg sm:text-xl text-gray-800 dark:text-gray-400 leading-tight break-words">
          New Property
        </h2>
        <p class="mt-1 text-xs sm:text-sm text-gray-500 break-words">
          Add a new property. Latitude &amp; longitude will be updated automatically from the address.
        </p>
      </div>

      {{-- Trigger --}}
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-4 flex-shrink-0 w-full sm:w-auto">
        <div x-data class="w-full sm:w-auto">
          <x-button variant="secondary" class="w-full sm:w-auto text-center whitespace-nowrap"
            @click="$dispatch('open-preview-panel', 'rooms-preview')">
            <span class="hidden sm:inline">Preview Room list</span>
            <span class="sm:hidden">Preview Rooms</span>
          </x-button>
        </div>

        <x-button variant="secondary" href="{{ route('properties.index') }}"
          class="w-full sm:w-auto text-center whitespace-nowrap">
          Back to List
        </x-button>
      </div>
    </div>
  </x-slot>


  {{-- Preview panel --}}
  @include('properties.__preview_panel', ['rooms' => $rooms])

  <x-card class="max-w-full">
    <form x-data="propertyForm()" x-init="init()" method="post" action="{{ route('properties.store') }}"
      enctype="multipart/form-data" @submit="handleSubmit($event)" class="max-w-full">
      @csrf

      {{-- Note: owner_id is automatically set in PropertyStoreRequest::prepareForValidation() for owners --}}

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 max-w-full">
        {{-- Left column: Image uploader --}}
        <div class="lg:col-span-1 max-w-full overflow-hidden">
          <x-form.label value="Property Photo" />

          <div
            class="mt-1 border-2 border-dashed rounded-xl sm:rounded-2xl p-3 sm:p-4 flex flex-col items-center justify-center text-center cursor-pointer transition-colors max-w-full overflow-hidden"
            :class="dragOver ? 'border-indigo-500 bg-indigo-50/40' :
                'border-gray-300 dark:border-gray-500 bg-gray-50/40 dark:bg-gray-800 dark:hover:bg-gray-900'"
            @click="$refs.file.click()" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
            @drop.prevent="handleDrop($event)">
            <template x-if="!previewUrl">
              <div class="text-gray-500 max-w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l5.5 5.5M21 7l-5.5 5.5M12 3v12" />
                </svg>
                <p class="mt-2 text-sm font-medium break-words">Drag &amp; drop or click to upload</p>
                <p class="mt-1 text-xs text-gray-400 break-words">
                  JPG, PNG, WebP — up to ~5MB
                </p>
              </div>
            </template>

            <template x-if="previewUrl">
              <div class="w-full max-w-full overflow-hidden">
                <img :src="previewUrl" alt="Preview"
                  class="rounded-xl object-cover h-48 w-full shadow-sm max-w-full" />
                <p class="mt-2 text-xs text-gray-500 break-words">
                  Click or drop a new file to replace the photo.
                </p>
                <template x-if="previewUrl && !hasFileSelected">
                  <div
                    class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800 max-w-full overflow-hidden">
                    <p class="font-semibold break-words">⚠️ Image preview restored</p>
                    <p class="mt-1 break-words">Please click above to re-select the image file. Browser security
                      requires this after a page refresh.</p>
                  </div>
                </template>
              </div>
            </template>

            <input type="file" name="photo" x-ref="file" class="hidden" @change="preview($event)"
              accept="image/*" />
          </div>

          @error('photo')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Right column: Form fields --}}
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 max-w-full overflow-visible">
          {{-- Admin can assign owner --}}
          @role('admin')
            <div class="md:col-span-2">
              <x-form.label value="Owner" />
              {{-- Expecting $owners = [id => name] from controller --}}
              <x-form.select name="owner_id" class="w-full">
                <option value="" disabled selected>— Select Owner —</option>
                @foreach ($owners ?? [] as $id => $name)
                  <option value="{{ $id }}" @selected(old('owner_id') == $id)>{{ $name }}
                  </option>
                @endforeach
              </x-form.select>
              <x-form.error :messages="$errors->get('owner_id')" />
            </div>
          @endrole

          {{-- Name --}}
          <div class="md:col-span-2">
            <x-form.label value="Name" />
            <x-form.input name="name" class="w-full" required :value="old('name')"
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
              :value="old('address')"
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
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.5">
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
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 019 9m-9 9a9 9 0 01-9-9" />
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
            <x-form.input name="latitude" class="w-full" x-model="latitude" :value="old('latitude')" inputmode="decimal"
              pattern="^[0-9]*\\.?[0-9]*$"
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
            <x-form.input name="longitude" class="w-full" x-model="longitude" :value="old('longitude')" inputmode="decimal"
              pattern="^[0-9]*\\.?[0-9]*$"
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

      <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row gap-2 sm:justify-end max-w-full">
        {{-- Plain save --}}
        <x-button type="submit" name="attach" value="none" x-bind:disabled="isGeocoding"
          x-bind:class="isGeocoding ? 'opacity-60 cursor-not-allowed' : ''" class="w-full sm:w-auto whitespace-nowrap">
          <span x-show="!isGeocoding">Save</span>
          <span x-show="isGeocoding">Fetching coordinates…</span>
        </x-button>

        {{-- Save + default rooms --}}
        <x-button type="submit" name="attach" value="rooms"
          class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 w-full sm:w-auto whitespace-nowrap"
          x-bind:disabled="isGeocoding" x-bind:class="isGeocoding ? 'opacity-60 cursor-not-allowed' : ''">
          <span class="hidden sm:inline">Save + Assign Default Rooms</span>
          <span class="sm:hidden">Save + Default Rooms</span>
        </x-button>

        <x-button variant="secondary" href="{{ route('properties.index') }}"
          class="w-full sm:w-auto whitespace-nowrap">
          Cancel
        </x-button>
      </div>
    </form>
  </x-card>

  {{-- Conditionally load Google Places API if using Google provider --}}
  @if(config('services.geocoding.provider') === 'google')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places"></script>
  @endif

  {{-- Alpine helpers --}}
  <script>
    function propertyForm() {
      return {
        address: @json(old('address', '')),
        latitude: @json(old('latitude', '')),
        longitude: @json(old('longitude', '')),
        previewUrl: null,
        dragOver: false,
        hasFileSelected: false, // Track if file input has a file

        // UX state
        isGeocoding: false,
        geocodeError: '',
        isSubmitting: false,
        geocodeTimeout: null,

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
          const savedPreview = sessionStorage.getItem('property_photo_preview');
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
                  sessionStorage.setItem('property_photo_preview', e.target.result);
                };
                reader.readAsDataURL(file);
              }
            }
          });

          // Only clear sessionStorage if form loads without any errors AND no file is selected
          // This means it was a successful submission (page redirected back fresh)
          @if ($errors->isEmpty())
            // Small delay to ensure file check completes first
            setTimeout(() => {
              if (!this.hasFileSelected) {
                sessionStorage.removeItem('property_photo_preview');
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
            sessionStorage.setItem('property_photo_preview', e.target.result);
          };
          reader.readAsDataURL(file);
        },

        handleDrop(evt) {
          this.dragOver = false;
          const file = evt.dataTransfer.files?.[0];
          if (!file) return;

          this.$refs.file.files = evt.dataTransfer.files;
          this.preview({
            target: {
              files: this.$refs.file.files
            }
          });
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
            await this.geocodeIfNeeded(true);

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
          if (!this.address) {
            this.latitude = '';
            this.longitude = '';
            return;
          }

          clearTimeout(this.geocodeTimeout);

          if (!force) {
            this.geocodeTimeout = setTimeout(() => this.fetchGeocode(), 800);
            return;
          }

          await this.fetchGeocode();
        },

        async fetchGeocode() {
          this.isGeocoding = true;
          this.geocodeError = '';

          try {
            const url =
              `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(this.address)}&format=json&limit=1`;
            const res = await fetch(url, {
              headers: {
                'Accept': 'application/json',
                'User-Agent': 'RoomReady/1.0'
              }
            });

            if (!res.ok) {
              throw new Error(`HTTP ${res.status}`);
            }

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
        },
      }
    }
  </script>
</x-app-layout>
