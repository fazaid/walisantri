<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut berisi pesan kesalahan default yang digunakan oleh
    | kelas validator. Beberapa aturan memiliki beberapa versi, seperti aturan
    | ukuran. Silakan sesuaikan pesan-pesan ini sesuai kebutuhan.
    |
    */

    'accepted'             => ':attribute harus diterima.',
    'accepted_if'          => ':attribute harus diterima ketika :other adalah :value.',
    'active_url'           => ':attribute bukan URL yang valid.',
    'after'                => ':attribute harus berupa tanggal setelah :date.',
    'after_or_equal'       => ':attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha'                => ':attribute hanya boleh berisi huruf.',
    'alpha_dash'           => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num'            => ':attribute hanya boleh berisi huruf dan angka.',
    'array'                => ':attribute harus berupa sebuah array.',
    'ascii'                => ':attribute hanya boleh berisi karakter dan simbol alfanumerik satu byte.',
    'before'               => ':attribute harus berupa tanggal sebelum :date.',
    'before_or_equal'      => ':attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between'              => [
        'array'   => ':attribute harus memiliki antara :min sampai :max item.',
        'file'    => ':attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => ':attribute harus bernilai antara :min sampai :max.',
        'string'  => ':attribute harus berisi antara :min sampai :max karakter.',
    ],
    'boolean'              => ':attribute harus bernilai true atau false.',
    'can'                  => ':attribute berisi nilai yang tidak diizinkan.',
    'confirmed'            => 'Konfirmasi :attribute tidak cocok.',
    'current_password'     => 'Kata sandi salah.',
    'date'                 => ':attribute bukan tanggal yang valid.',
    'date_equals'          => ':attribute harus berupa tanggal yang sama dengan :date.',
    'date_format'          => ':attribute tidak cocok dengan format :format.',
    'decimal'              => ':attribute harus memiliki :decimal angka desimal.',
    'declined'             => ':attribute harus ditolak.',
    'declined_if'          => ':attribute harus ditolak ketika :other adalah :value.',
    'different'            => ':attribute dan :other harus berbeda.',
    'digits'               => ':attribute harus terdiri dari :digits digit.',
    'digits_between'       => ':attribute harus terdiri dari antara :min dan :max digit.',
    'dimensions'           => ':attribute memiliki dimensi gambar yang tidak valid.',
    'distinct'             => ':attribute memiliki nilai yang duplikat.',
    'doesnt_end_with'      => ':attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with'    => ':attribute tidak boleh diawali dengan salah satu dari: :values.',
    'email'                => ':attribute harus berupa alamat email yang valid.',
    'ends_with'            => ':attribute harus diakhiri dengan salah satu dari: :values.',
    'enum'                 => ':attribute yang dipilih tidak valid.',
    'exists'               => ':attribute yang dipilih tidak valid.',
    'extensions'           => ':attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file'                 => ':attribute harus berupa sebuah berkas.',
    'filled'               => ':attribute harus memiliki nilai.',
    'gt'                   => [
        'array'   => ':attribute harus memiliki lebih dari :value item.',
        'file'    => ':attribute harus berukuran lebih dari :value kilobyte.',
        'numeric' => ':attribute harus bernilai lebih dari :value.',
        'string'  => ':attribute harus berisi lebih dari :value karakter.',
    ],
    'gte'                  => [
        'array'   => ':attribute harus memiliki :value item atau lebih.',
        'file'    => ':attribute harus berukuran lebih dari atau sama dengan :value kilobyte.',
        'numeric' => ':attribute harus bernilai lebih dari atau sama dengan :value.',
        'string'  => ':attribute harus berisi lebih dari atau sama dengan :value karakter.',
    ],
    'hex_color'            => ':attribute harus berupa warna heksadesimal yang valid.',
    'image'                => ':attribute harus berupa gambar.',
    'in'                   => ':attribute yang dipilih tidak valid.',
    'in_array'             => ':attribute tidak ada di dalam :other.',
    'integer'              => ':attribute harus berupa bilangan bulat.',
    'ip'                   => ':attribute harus berupa alamat IP yang valid.',
    'ipv4'                 => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6'                 => ':attribute harus berupa alamat IPv6 yang valid.',
    'json'                 => ':attribute harus berupa string JSON yang valid.',
    'lowercase'            => ':attribute harus berupa huruf kecil.',
    'lt'                   => [
        'array'   => ':attribute harus memiliki kurang dari :value item.',
        'file'    => ':attribute harus berukuran kurang dari :value kilobyte.',
        'numeric' => ':attribute harus bernilai kurang dari :value.',
        'string'  => ':attribute harus berisi kurang dari :value karakter.',
    ],
    'lte'                  => [
        'array'   => ':attribute tidak boleh memiliki lebih dari :value item.',
        'file'    => ':attribute harus berukuran kurang dari atau sama dengan :value kilobyte.',
        'numeric' => ':attribute harus bernilai kurang dari atau sama dengan :value.',
        'string'  => ':attribute harus berisi kurang dari atau sama dengan :value karakter.',
    ],
    'mac_address'          => ':attribute harus berupa alamat MAC yang valid.',
    'max'                  => [
        'array'   => ':attribute tidak boleh memiliki lebih dari :max item.',
        'file'    => ':attribute tidak boleh berukuran lebih dari :max kilobyte.',
        'numeric' => ':attribute tidak boleh bernilai lebih dari :max.',
        'string'  => ':attribute tidak boleh berisi lebih dari :max karakter.',
    ],
    'max_digits'           => ':attribute tidak boleh memiliki lebih dari :max digit.',
    'mimes'                => ':attribute harus berupa berkas dengan tipe: :values.',
    'mimetypes'            => ':attribute harus berupa berkas dengan tipe: :values.',
    'min'                  => [
        'array'   => ':attribute harus memiliki minimal :min item.',
        'file'    => ':attribute harus berukuran minimal :min kilobyte.',
        'numeric' => ':attribute harus bernilai minimal :min.',
        'string'  => ':attribute harus berisi minimal :min karakter.',
    ],
    'min_digits'           => ':attribute harus memiliki minimal :min digit.',
    'missing'              => ':attribute harus tidak ada.',
    'missing_if'           => ':attribute harus tidak ada ketika :other adalah :value.',
    'missing_unless'       => ':attribute harus tidak ada kecuali :other adalah :value.',
    'missing_with'         => ':attribute harus tidak ada ketika :values ada.',
    'missing_with_all'     => ':attribute harus tidak ada ketika :values ada.',
    'multiple_of'          => ':attribute harus merupakan kelipatan dari :value.',
    'not_in'               => ':attribute yang dipilih tidak valid.',
    'not_regex'            => 'Format :attribute tidak valid.',
    'numeric'              => ':attribute harus berupa angka.',
    'password'             => [
        'letters'       => ':attribute harus mengandung minimal satu huruf.',
        'mixed'         => ':attribute harus mengandung minimal satu huruf besar dan satu huruf kecil.',
        'numbers'       => ':attribute harus mengandung minimal satu angka.',
        'symbols'       => ':attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute yang diberikan telah muncul dalam kebocoran data. Silakan pilih :attribute yang berbeda.',
    ],
    'present'              => ':attribute harus ada.',
    'present_if'           => ':attribute harus ada ketika :other adalah :value.',
    'present_unless'       => ':attribute harus ada kecuali :other adalah :value.',
    'present_with'         => ':attribute harus ada ketika :values ada.',
    'present_with_all'     => ':attribute harus ada ketika :values ada.',
    'prohibited'           => ':attribute dilarang.',
    'prohibited_if'        => ':attribute dilarang ketika :other adalah :value.',
    'prohibited_unless'    => ':attribute dilarang kecuali :other ada di dalam :values.',
    'prohibits'            => ':attribute melarang :other untuk ada.',
    'regex'                => 'Format :attribute tidak valid.',
    'required'             => ':attribute wajib diisi.',
    'required_array_keys'  => ':attribute harus berisi entri untuk: :values.',
    'required_if'          => ':attribute wajib diisi ketika :other adalah :value.',
    'required_if_accepted' => ':attribute wajib diisi ketika :other diterima.',
    'required_unless'      => ':attribute wajib diisi kecuali :other ada di dalam :values.',
    'required_with'        => ':attribute wajib diisi ketika :values ada.',
    'required_with_all'    => ':attribute wajib diisi ketika :values ada.',
    'required_without'     => ':attribute wajib diisi ketika :values tidak ada.',
    'required_without_all' => ':attribute wajib diisi ketika tidak ada satupun dari :values yang ada.',
    'same'                 => ':attribute dan :other harus cocok.',
    'size'                 => [
        'array'   => ':attribute harus berisi :size item.',
        'file'    => ':attribute harus berukuran :size kilobyte.',
        'numeric' => ':attribute harus bernilai :size.',
        'string'  => ':attribute harus berisi :size karakter.',
    ],
    'starts_with'          => ':attribute harus diawali dengan salah satu dari: :values.',
    'string'               => ':attribute harus berupa string.',
    'timezone'             => ':attribute harus berupa zona waktu yang valid.',
    'unique'               => ':attribute sudah digunakan.',
    'uploaded'             => ':attribute gagal diunggah.',
    'uppercase'            => ':attribute harus berupa huruf besar.',
    'url'                  => ':attribute harus berupa URL yang valid.',
    'ulid'                 => ':attribute harus berupa ULID yang valid.',
    'uuid'                 => ':attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi Kustom
    |--------------------------------------------------------------------------
    |
    | Di sini Anda dapat menentukan pesan validasi kustom untuk atribut dengan
    | menggunakan konvensi "attribute.rule" dalam penamaan baris. Ini
    | mempercepat penentuan baris bahasa kustom yang spesifik.
    |
    */

    'custom' => [
        'slug' => [
            'unique' => 'Subdomain ini sudah dipakai pesantren lain. Silakan pilih subdomain lain.',
        ],
        'email' => [
            'unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Atribut Validasi Kustom
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut digunakan untuk menukar placeholder :attribute
    | dengan sesuatu yang lebih ramah pembaca seperti "Alamat Email" alih-alih
    | "email". Ini membantu membuat pesan lebih ekspresif.
    |
    */

    'attributes' => [
        'nama_pesantren'        => 'nama pesantren',
        'slug'                  => 'subdomain',
        'admin_name'            => 'nama admin',
        'email'                 => 'email',
        'password'              => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
    ],

];
