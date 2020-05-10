<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Machine_model::class, function (Faker\Generator $faker) {
    $machines = [
        ['iMac', 'iMac9,1', 'iMac (20-inch, Mid 2009)'],
        ['Macmini', 'Macmini7,1', 'Mac mini (Late 2014)'],
        ['Macmini', 'Macmini5,1', 'Mac mini (Mid 2011)'],
        ['MacBook Pro', 'MacBookPro12,1', 'MacBook Pro (Retina, 13-inch, Early 2015)'],
        ['MacBook Pro', 'MacBookPro8,1', 'MacBook Pro (13-inch, Late 2011)'],
        ['MacBook Pro', 'MacBookPro9,2', 'MacBook Pro (13-inch, Mid 2012)'],
        ['Macmini', 'Macmini6,2', 'Mac mini (Late 2012)'],
        ['MacBook', 'MacBook7,1', 'MacBook (13-inch, Mid 2010)'],
        ['iMac', 'iMac14,2', 'iMac (27-inch, Late 2013)'],
        ['iMac', 'iMac10,1', 'iMac (21.5-inch, Late 2009)'],
        ['iMac', 'iMac14,4', 'iMac (21.5-inch, Mid 2014)'],
        ['iMac', 'iMac12,1', 'iMac (21.5-inch, Mid 2011)'],
        ['iMac', 'iMac16,2', 'iMac (21.5-inch, Late 2015)'],
    ];

    $oses = [
        ['101206', '16G29'],
        ['101301', '17B48'],
        ['101503', '19D76'],
    ];

    list($machine_name, $machine_model, $machine_desc) = $faker->randomElement($machines);
    list($os_version, $build) = $faker->randomElement($oses);

    $computerName = $faker->firstName() . '\'s ' . $machine_name;

    return [
        'hostname' => $computerName . '.local',
        'machine_model' => $machine_model,
        'machine_desc' => $machine_desc,
        'img_url' => '',
        'cpu' => $faker->text,
        'current_processor_speed' => $faker->randomFloat(2, 1, 4) . " GHz",
        'cpu_arch' => 'x86_64',
        'os_version' => $os_version,
        'physical_memory' => $faker->randomElement([4,8,16,32]),
        'platform_uuid' => $faker->uuid,
        'number_processors' => $faker->randomElement([2,4,6,8]),
        'SMC_version_system' => $faker->randomFloat(2, 1, 3) . 'f' . $faker->randomDigit,
        'boot_rom_version' => $faker->regexify('[IMBP]{2}\.[0-9]{4}\.[A-Z]+'),
        'bus_speed' => $faker->randomElement([null, '1.07 Ghz']),
        'computer_name' => $computerName,
        'l2_cache' => $faker->randomElement([null, '3 MB', '6 MB']),
        'machine_name' => $machine_name,
        'packages' => 1,
        'buildversion' => $build,
    ];
});
