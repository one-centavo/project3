<?php

use Livewire\Component;

new class extends Component {
    public string $uuid = '';

    public string $dni = '';

    public string $first_name = '';

    public ?string $second_name = null;

    public string $first_last_name = '';

    public ?string $second_last_name = null;

    public string $email = '';

    public string $phone_number = '';

    public string $address = '';

    public bool $is_sync = false;
};
?>

<div>
    {{-- The only way to do great work is to love what you do. - Steve Jobs --}}
</div>
