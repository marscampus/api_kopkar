<?php

namespace App\Jobs;

use App\Helpers\GetterSetter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateKodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $member;

    public function __construct($member)
    {
        $this->member = $member;
    }

    public function handle()
    {
        $result = [];
        foreach ($this->member as $member) {
            $result[] = ['Anggota' => $member];
            GetterSetter::setRekening('0');
        }
        // Simpan hasil ke database, response API, atau lakukan apa yang diinginkan
    }
}
