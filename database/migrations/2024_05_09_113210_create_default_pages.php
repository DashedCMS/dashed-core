<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!\Dashed\DashedPages\Models\Page::where('is_home', 1)->count()) {
            $page = new \Dashed\DashedPages\Models\Page();
            $page->setTranslation('name', 'nl', 'Home');
            $page->setTranslation('slug', 'nl', 'home');
            $page->is_home = 1;
            $page->save();

            $page = new \Dashed\DashedPages\Models\Page();
            $page->setTranslation('name', 'nl', 'Contact');
            $page->setTranslation('slug', 'nl', 'contact');
            $page->save();
        }

        $page = new \Dashed\DashedPages\Models\Page();
        $page->setTranslation('name', 'nl', 'Login');
        $page->setTranslation('slug', 'nl', 'login');
        $page->setTranslation('content', 'nl', [
            [
                'data' => [],
                'type' => 'login-block',
            ]
        ]);
        $page->save();

        \Dashed\DashedCore\Models\Customsetting::set('login_page_id', $page->id);

        $page = new \Dashed\DashedPages\Models\Page();
        $page->setTranslation('name', 'nl', 'Account');
        $page->setTranslation('slug', 'nl', 'account');
        $page->setTranslation('content', 'nl', [
            [
                'data' => [],
                'type' => 'account-block',
            ]
        ]);
        $page->save();

        \Dashed\DashedCore\Models\Customsetting::set('account_page_id', $page->id);

        $page = new \Dashed\DashedPages\Models\Page();
        $page->setTranslation('name', 'nl', 'Wachtwoord vergeten');
        $page->setTranslation('slug', 'nl', 'wachtwoord-vergeten');
        $page->setTranslation('content', 'nl', [
            [
                'data' => [],
                'type' => 'forgot-password-block',
            ]
        ]);
        $page->save();

        \Dashed\DashedCore\Models\Customsetting::set('forgot_password_page_id', $page->id);

        $page = new \Dashed\DashedPages\Models\Page();
        $page->setTranslation('name', 'nl', 'Reset wachtwoord');
        $page->setTranslation('slug', 'nl', 'reset-wachtwoord');
        $page->setTranslation('content', 'nl', [
            [
                'data' => [],
                'type' => 'reset-password-block',
            ]
        ]);
        $page->save();

        \Dashed\DashedCore\Models\Customsetting::set('reset_password_page_id', $page->id);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metadata');
    }
};
