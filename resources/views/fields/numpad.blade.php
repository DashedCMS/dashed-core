<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="numpadEuro({
            entangled: $wire.$entangle('{{ $getStatePath() }}'),
            storesCents: @js($storesCents),
            allowNegative: @js($allowNegative),
            minCents: @js($minCents),
            maxCents: @js($maxCents),
        })"
        class="flex flex-col gap-4"
    >
        <input
            type="hidden"
            x-ref="livewireInput"
            wire:model.live="{{ $getStatePath() }}"
        />

        <div class="flex items-end justify-between">
            <div class="text-4xl font-semibold tabular-nums select-none ml-auto">
                {{ $currencySymbol }} <span x-text="formatted"></span>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 select-none">
            <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                <button type="button"
                        class="p-5 rounded-2xl shadow border text-2xl font-semibold hover:shadow-md active:scale-95 active:bg-primary-500 transition"
                        x-on:click="press(n)" x-text="n"></button>
            </template>

            <button type="button"
                    class="p-5 rounded-2xl shadow border text-lg font-medium hover:shadow-md active:scale-95 active:bg-primary-500 transition"
                    x-on:click="clearAll()" title="Wissen">C
            </button>

            <button type="button"
                    class="p-5 rounded-2xl shadow border text-2xl font-semibold hover:shadow-md active:scale-95 active:bg-primary-500 transition"
                    x-on:click="press(0)">0
            </button>

            <button type="button"
                    class="p-5 rounded-2xl shadow border text-lg font-medium hover:shadow-md active:scale-95 active:bg-primary-500 transition"
                    x-on:click="backspace()" title="Delete">&larr;
            </button>
        </div>

        <template x-if="validationMessage">
            <p class="text-xs text-red-600" x-text="validationMessage"></p>
        </template>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('numpadEuro', ({entangled, storesCents, allowNegative, minCents, maxCents}) => ({
                entangled,
                storesCents,
                allowNegative,
                minCents,
                maxCents,

                digits: '0',
                negative: false,
                validationMessage: '',

                init() {
                    const raw = this.entangled ?? 0;
                    let cents = this.storesCents ? parseInt(raw || 0, 10) : Math.round(parseFloat(raw || 0) * 100);
                    if (cents < 0) {
                        this.negative = true;
                        cents = Math.abs(cents);
                    }
                    this.digits = String(isNaN(cents) ? 0 : Math.max(0, cents));

                    this.$watch('entangled', (value) => {
                        if (value !== null && value !== '' && !this._initialized) {
                            this._loadInitialValue(value)
                        }
                    })

                    if (this.entangled !== null && this.entangled !== '') {
                        this._loadInitialValue(this.entangled)
                    }

                    this._pushToWire();

                    this.$wire.on('resetNumpad', (notification) => {
                        this._resetNumpad()
                    })
                },

                get signedCents() {
                    const c = parseInt(this.digits.replace(/\D/g, '') || '0', 10);
                    return this.negative ? -c : c;
                },

                get formatted() {
                    return this._formatNl(this.signedCents);
                },

                press(n) {
                    if (!Number.isInteger(n) || n < 0 || n > 9) return;
                    this.validationMessage = '';
                    this.digits = (this.digits + String(n)).replace(/^0+(?=\d)/, '');
                    this._enforceBounds();
                    this._pushToWire();
                },

                backspace() {
                    this.validationMessage = '';
                    this.digits = this.digits.length <= 1 ? '0' : this.digits.slice(0, -1);
                    this._pushToWire();
                },

                clearAll() {
                    this.validationMessage = '';
                    this.digits = '0';
                    this.negative = false;
                    this._pushToWire();
                },

                _pushToWire() {
                    const cents = this.signedCents;

                    if (this.minCents !== null && cents < this.minCents) {
                        this.validationMessage = `Minimum is ${this._formatNl(this.minCents)}.`;
                    } else if (this.maxCents !== null && cents > this.maxCents) {
                        this.validationMessage = `Maximum is ${this._formatNl(this.maxCents)}.`;
                    } else {
                        this.validationMessage = '';
                    }

                    const val = this.storesCents ? cents : (cents / 100).toFixed(2);
                    this.$refs.livewireInput.value = val;
                    this.$refs.livewireInput.dispatchEvent(new Event('input', {bubbles: true}));

                    this.entangled = val;
                },

                _enforceBounds() {
                    if (this.maxCents !== null && this.signedCents > this.maxCents && this.digits.length > 1) {
                        this.digits = this.digits.slice(0, -1);
                    }
                },

                _formatNl(cents) {
                    const abs = Math.abs(cents);
                    const euros = Math.floor(abs / 100);
                    const centPart = String(abs % 100).padStart(2, '0');
                    const eurosStr = euros.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const sign = cents < 0 ? '-' : '';
                    return `${sign}${eurosStr},${centPart}`;
                },

                _resetNumpad() {
                    this.digits = '0'
                    this.negative = false
                    this.validationMessage = ''
                    this._pushToWire()
                },

                _loadInitialValue(value) {
                    let cents = 0

                    if (this.storesCents) {
                        cents = parseInt(value || 0, 10)
                    } else {
                        cents = Math.round(parseFloat(value || 0) * 100)
                    }

                    if (isNaN(cents)) cents = 0

                    this.negative = cents < 0
                    cents = Math.abs(cents)

                    this.digits = String(cents)
                    this._initialized = true
                    this._pushToWire()
                },
            }))
        })
    </script>
</x-dynamic-component>
