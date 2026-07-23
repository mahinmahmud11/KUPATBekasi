<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminCommand extends Command
{
    protected $signature = 'kupat:make-admin';

    protected $description = 'Membuat akun administrator baru atau mempromosikan akun yang sudah ada';

    public function handle(): int
    {
        $email = trim((string) $this->ask('Alamat Email'));

        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('Email tidak valid: '.implode(', ', $validator->errors()->all()));

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->info("Akun dengan email {$email} sudah terdaftar.");

            if ($user->is_admin) {
                $this->info('Akun ini sudah memiliki hak akses administrator. Tidak ada perubahan yang dilakukan.');

                return self::SUCCESS;
            }

            if ($this->confirm("Apakah Anda ingin mempromosikan akun {$user->name} menjadi administrator?", false)) {
                $user->is_admin = true;
                $user->save();
                $this->info("Akun {$user->name} berhasil dipromosikan menjadi administrator.");

                return self::SUCCESS;
            }

            $this->warn('Promosi dibatalkan.');

            return self::SUCCESS;
        }

        $this->info('Membuat akun administrator baru.');
        $name = trim((string) $this->ask('Nama Lengkap'));

        if (blank($name)) {
            $this->error('Nama wajib diisi.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (minimal 8 karakter)');
        $passwordConfirmation = (string) $this->secret('Konfirmasi Password');

        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            $this->error('Validasi password gagal: '.implode(', ', $validator->errors()->all()));

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->is_admin = true;
        $user->save();

        $this->info("Akun administrator {$user->name} berhasil dibuat.");

        return self::SUCCESS;
    }
}
