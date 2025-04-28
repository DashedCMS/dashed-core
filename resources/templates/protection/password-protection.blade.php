<section class="py-24 overflow-hidden text-primary-800">
    <x-container>
        <div class="grid gap-12 divide-primary-800 max-w-2xl mx-auto">
            <form wire:submit.prevent="checkPassword" class="flex flex-col gap-6 pl-12">
                <h1 class="text-3xl font-bold text-primary-800">{{Translation::get('get-access-to-page', 'password-protection', 'Krijg toegang tot :name:', 'text', [
                    'name' => $model->name
                ])}}</h1>

                <x-fields.input required type="password" model="password" id="password" class="w-full"
                                :label="Translation::get('password', 'password-protection', 'Wachtwoord')"/>

                <button
                    class="mt-auto button button--primary">{{Translation::get('go-to-page', 'password-protection', 'Doorgaan naar pagina')}}</button>
            </form>
        </div>
    </x-container>

    <x-dashed-core::global-blocks name="password-protection-page"/>
</section>
