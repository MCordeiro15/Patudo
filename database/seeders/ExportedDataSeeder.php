<?php


namespace Database\Seeders;

use App\Models\User;
use App\Models\Petsitter;
use App\Models\Animal;
use App\Models\Post;
use App\Models\Booking;
use App\Models\Review;
use App\Models\AnimalBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SEEDER GERADO AUTOMATICAMENTE - NÃO EDITE MANUALMENTE
 * Este arquivo é gerado pelo comando php artisan db:export
 * Última atualização: 2026-11-13 20:25:34
 */
class ExportedDataSeeder extends Seeder
{
    public function run(): void
    {
        // Desabilitar eventos durante o seed
        \Illuminate\Support\Facades\Event::fake();

        // Export Users (12 users)
        $users = array (
  0 => 
  array (
    'id' => 1,
    'name' => 'Sofia Mendes',
    'email' => 'sofia@patudo.test',
    'password' => '$2y$12$xsn0hYa6FDSJTjHR3gs.WOVQ2aoaHvMKwRYnwyDf.6eB.HFDm4zeq',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 2100,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:02.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '912345678',
    'is_admin' => false,
    'is_petsitter' => true,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 2094,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:02.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1588,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:02.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  1 => 
  array (
    'id' => 2,
    'name' => 'Tiago Ferreira',
    'email' => 'tiago@patudo.test',
    'password' => '$2y$12$M/l1H25i8tgbYERPW5Gf8.WPv1Ow0/LzhrMfANiwpv0dMiJScyt0m',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 2101,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '923456789',
    'is_admin' => false,
    'is_petsitter' => true,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 2083,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 2082,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  2 => 
  array (
    'id' => 3,
    'name' => 'João Santos',
    'email' => 'joao@patudo.test',
    'password' => '$2y$12$fariqkOfKpfIqIgl.F2oZ.XI34tJQH.nTnu9lEMAus.SkzqP6P7s.',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1625,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '881298739',
    'is_admin' => false,
    'is_petsitter' => true,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1623,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1643,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:17.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  3 => 
  array (
    'id' => 4,
    'name' => 'Inês Rodrigues',
    'email' => 'ines@patudo.test',
    'password' => '$2y$12$TA5isIUFnMwHgqrOSg879.asdDs5Xdrox6/C4i6sEu9F453OpMUZS',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1635,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '945678901',
    'is_admin' => false,
    'is_petsitter' => true,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1636,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1634,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  4 => 
  array (
    'id' => 5,
    'name' => 'Maria Oliveira',
    'email' => 'maria@patudo.test',
    'password' => '$2y$12$nwJPRjf/stfnZkLUS8pKh.R75px9SJ8e0tp5g9TEQHt0ZWg2n.PmW',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1633,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '956789012',
    'is_admin' => false,
    'is_petsitter' => true,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1628,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1629,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  5 => 
  array (
    'id' => 6,
    'name' => 'Rui Costa',
    'email' => 'rui@patudo.test',
    'password' => '$2y$12$0eXyD0o83WD.dj3o9EuBHeW/HeDx9iouQJFR5G/MVllCXgDYp2nnG',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1630,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '967890123',
    'is_admin' => false,
    'is_petsitter' => true,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1632,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 2081,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  6 => 
  array (
    'id' => 7,
    'name' => 'Admin',
    'email' => 'admin@admin.com',
    'password' => '$2y$12$RhyBojY1TqUyzuSipFhHv.qoQd0RswePfezug9kHwpAdZrZe6sPKW',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1631,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '910000000',
    'is_admin' => true,
    'is_petsitter' => true,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1638,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1644,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  7 => 
  array (
    'id' => 8,
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => '$2y$12$g0k6EdxAGUcG80sERS5uKu0oq8WAdKVBgCMsoEC.c2CHZPlbvYvUq',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1645,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '920000000',
    'is_admin' => false,
    'is_petsitter' => false,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1646,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1647,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  8 => 
  array (
    'id' => 9,
    'name' => 'Miguel',
    'email' => 'miguel@gmail.com',
    'password' => '$2y$12$ufBsw1MZEwUtMddozFg25egymq1kkDFKfnPRWcRpbxn3gSRjd5h7u',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1648,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '930000000',
    'is_admin' => false,
    'is_petsitter' => false,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1649,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1650,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  9 => 
  array (
    'id' => 10,
    'name' => 'Ana Silva',
    'email' => 'client1@patudo.test',
    'password' => '$2y$12$ct5EekjbmHWli5/7v7OuGeRDAu3kHZSxjcq7qD8gAdF1wytfAwFNy',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1651,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '940000000',
    'is_admin' => false,
    'is_petsitter' => false,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1652,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1653,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  10 => 
  array (
    'id' => 11,
    'name' => 'Pedro Costa',
    'email' => 'client2@patudo.test',
    'password' => '$2y$12$W.z4zYHQpC.4tYQE/VmFtOpi4vpPIhSP/t/JjfiNrUvb/6zqroelC',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1654,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '950000000',
    'is_admin' => false,
    'is_petsitter' => false,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1655,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1656,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  11 => 
  array (
    'id' => 12,
    'name' => 'Carla Mendes',
    'email' => 'client3@patudo.test',
    'password' => '$2y$12$swonSgYOpWJSFt3b25rQ8eC9F5/Y9pOyejqPb8X1eOSHTx.ZdsyE2',
    'email_verified_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1657,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'phone' => '960000000',
    'is_admin' => false,
    'is_petsitter' => false,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1658,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1659,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
);
        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'email_verified_at' => $userData['email_verified_at'],
                    'phone' => $userData['phone'],
                    'is_admin' => $userData['is_admin'],
                    'is_petsitter' => $userData['is_petsitter'],
                ]
            );
        }

        // Export Petsitters (7 petsitters)
        $petsitters = array (
  0 => 
  array (
    'id' => NULL,
    'user_id' => 1,
    'biography' => 'Apaixonada por animais desde criança, tenho experiência com todas as raças de cães e gatos. Ofereço cuidados personalizados e muitos mimos.',
    'short_bio' => 'Cuidadora experiente em Lisboa',
    'experience' => 'Cuidadora de animais há 5 anos. Especializada em cães de grande porte e gatos. Disponibilidade total e muito amor para dar!',
    'service_location' => 'Lisboa, Portugal',
    'city' => 'Lisboa',
    'age' => 28,
    'price_per_day' => 12,
    'average_rating' => 5,
    'accepted_sizes' => 
    array (
      0 => 'small',
      1 => 'medium',
      2 => 'large',
    ),
    'sex' => 'female',
    'accepted_animal_types' => 
    array (
      0 => 'dog',
      1 => 'cat',
    ),
    'identification_documents' => 'document_1.pdf',
    'approval_status' => 'approved',
    'availability_days' => 
    array (
      0 => 'monday',
      1 => 'tuesday',
      2 => 'wednesday',
      3 => 'thursday',
      4 => 'friday',
      5 => 'saturday',
    ),
    'profile_photo' => NULL,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 2098,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:02.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1670,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  1 => 
  array (
    'id' => NULL,
    'user_id' => 2,
    'biography' => 'Estudante de veterinária no Porto. Nos tempos livres, cuido de animais e faço passeios com cães pela cidade e pela praia.',
    'short_bio' => 'Estudante de veterinária no Porto',
    'experience' => 'Estudante de veterinária com 3 anos de experiência em cuidados com animais. Adoro passeios longos com cães.',
    'service_location' => 'Porto, Portugal',
    'city' => 'Porto',
    'age' => 24,
    'price_per_day' => 10,
    'average_rating' => 5,
    'accepted_sizes' => 
    array (
      0 => 'small',
      1 => 'medium',
      2 => 'large',
    ),
    'sex' => 'male',
    'accepted_animal_types' => 
    array (
      0 => 'dog',
      1 => 'cat',
    ),
    'identification_documents' => 'document_2.pdf',
    'approval_status' => 'approved',
    'availability_days' => 
    array (
      0 => 'monday',
      1 => 'tuesday',
      2 => 'wednesday',
      3 => 'thursday',
      4 => 'friday',
      5 => 'saturday',
    ),
    'profile_photo' => NULL,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1669,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1668,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  2 => 
  array (
    'id' => NULL,
    'user_id' => 3,
    'biography' => 'Técnico veterinário qualificado. Ofereço cuidados de saúde básicos, administração de medicação e acompanhamento de animais idosos.',
    'short_bio' => 'Técnico veterinário em Braga',
    'experience' => 'Técnico veterinário com 7 anos de experiência. Cuidado especializado para animais com necessidades especiais.',
    'service_location' => 'Braga, Portugal',
    'city' => 'Braga',
    'age' => 30,
    'price_per_day' => 11,
    'average_rating' => 4,
    'accepted_sizes' => 
    array (
      0 => 'small',
      1 => 'medium',
      2 => 'large',
    ),
    'sex' => 'male',
    'accepted_animal_types' => 
    array (
      0 => 'dog',
      1 => 'cat',
    ),
    'identification_documents' => 'document_3.pdf',
    'approval_status' => 'approved',
    'availability_days' => 
    array (
      0 => 'monday',
      1 => 'tuesday',
      2 => 'wednesday',
      3 => 'thursday',
      4 => 'friday',
      5 => 'saturday',
    ),
    'profile_photo' => NULL,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1667,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1666,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  3 => 
  array (
    'id' => NULL,
    'user_id' => 4,
    'biography' => 'Apaixonada por animais e natureza. Faço passeios diários e ofereço um ambiente acolhedor para os seus companheiros de quatro patas.',
    'short_bio' => 'Entusiasta de animais em Coimbra',
    'experience' => 'Jovem entusiasta de animais com experiência em pet sitting e dog walking. Adoro todos os tipos de animais!',
    'service_location' => 'Coimbra, Portugal',
    'city' => 'Coimbra',
    'age' => 22,
    'price_per_day' => 9,
    'average_rating' => 5,
    'accepted_sizes' => 
    array (
      0 => 'small',
      1 => 'medium',
      2 => 'large',
    ),
    'sex' => 'female',
    'accepted_animal_types' => 
    array (
      0 => 'dog',
      1 => 'cat',
    ),
    'identification_documents' => 'document_4.pdf',
    'approval_status' => 'approved',
    'availability_days' => 
    array (
      0 => 'monday',
      1 => 'tuesday',
      2 => 'wednesday',
      3 => 'thursday',
      4 => 'friday',
      5 => 'saturday',
    ),
    'profile_photo' => NULL,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1664,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1665,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  4 => 
  array (
    'id' => NULL,
    'user_id' => 5,
    'biography' => 'Profissional certificada em comportamento animal. Ofereço cuidados especializados para animais com necessidades comportamentais.',
    'short_bio' => 'Comportamentalista animal em Aveiro',
    'experience' => 'Comportamentalista animal com 10 anos de experiência. Especialista em animais com ansiedade de separação.',
    'service_location' => 'Aveiro, Portugal',
    'city' => 'Aveiro',
    'age' => 35,
    'price_per_day' => 13,
    'average_rating' => 4,
    'accepted_sizes' => 
    array (
      0 => 'small',
      1 => 'medium',
      2 => 'large',
    ),
    'sex' => 'female',
    'accepted_animal_types' => 
    array (
      0 => 'dog',
      1 => 'cat',
    ),
    'identification_documents' => 'document_5.pdf',
    'approval_status' => 'approved',
    'availability_days' => 
    array (
      0 => 'monday',
      1 => 'tuesday',
      2 => 'wednesday',
      3 => 'thursday',
      4 => 'friday',
      5 => 'saturday',
    ),
    'profile_photo' => NULL,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1663,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1661,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:05.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  5 => 
  array (
    'id' => NULL,
    'user_id' => 6,
    'biography' => 'Treinador canino profissional. Ofereço passeios ativos, sessões de treino e muito carinho para os seus animais.',
    'short_bio' => 'Treinador canino em Faro',
    'experience' => 'Treinador canino com 4 anos de experiência. Combino cuidados com sessões de treino para enriquecer a vida do seu pet.',
    'service_location' => 'Faro, Portugal',
    'city' => 'Faro',
    'age' => 26,
    'price_per_day' => 10,
    'average_rating' => 5,
    'accepted_sizes' => 
    array (
      0 => 'small',
      1 => 'medium',
      2 => 'large',
    ),
    'sex' => 'male',
    'accepted_animal_types' => 
    array (
      0 => 'dog',
      1 => 'cat',
    ),
    'identification_documents' => 'document_6.pdf',
    'approval_status' => 'approved',
    'availability_days' => 
    array (
      0 => 'monday',
      1 => 'tuesday',
      2 => 'wednesday',
      3 => 'thursday',
      4 => 'friday',
      5 => 'saturday',
    ),
    'profile_photo' => NULL,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1662,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:03.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1679,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:05.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
  6 => 
  array (
    'id' => NULL,
    'user_id' => 7,
    'biography' => 'Administrador do Patudo com perfil de petsitter de teste.',
    'short_bio' => 'Admin do Patudo',
    'experience' => 'Experiência em cuidados com animais de estimação.',
    'service_location' => 'São Miguel, Açores',
    'city' => 'São Miguel',
    'age' => 25,
    'price_per_day' => 15,
    'average_rating' => 4,
    'accepted_sizes' => 
    array (
      0 => 'small',
      1 => 'medium',
      2 => 'large',
    ),
    'sex' => 'male',
    'accepted_animal_types' => 
    array (
      0 => 'dog',
      1 => 'cat',
      2 => 'bird',
      3 => 'rabbit',
    ),
    'identification_documents' => 'document_admin.pdf',
    'approval_status' => 'approved',
    'availability_days' => 
    array (
      0 => 'monday',
      1 => 'tuesday',
      2 => 'wednesday',
      3 => 'thursday',
      4 => 'friday',
      5 => 'saturday',
      6 => 'sunday',
    ),
    'profile_photo' => NULL,
    'created_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1680,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:04.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
    'updated_at' => 
    \Carbon\CarbonImmutable::__set_state(array(
       'endOfTime' => false,
       'startOfTime' => false,
       'constructedObjectId' => 1681,
       'clock' => NULL,
       'localMonthsOverflow' => NULL,
       'localYearsOverflow' => NULL,
       'localStrictModeEnabled' => NULL,
       'localHumanDiffOptions' => NULL,
       'localToStringFormat' => NULL,
       'localSerializer' => NULL,
       'localMacros' => NULL,
       'localGenericMacros' => NULL,
       'localFormatFunction' => NULL,
       'localTranslator' => NULL,
       'dumpProperties' => 
      array (
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
      ),
       'dumpLocale' => NULL,
       'dumpDateProperties' => NULL,
       'date' => '2026-11-13 20:25:05.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    )),
  ),
);
        foreach ($petsitters as $pData) {
            $petsitter = Petsitter::where('user_id', $pData['user_id'])->first();
            if ($petsitter) {
                $petsitter->update([
                    'biography' => $pData['biography'],
                    'short_bio' => $pData['short_bio'],
                    'experience' => $pData['experience'],
                    'service_location' => $pData['service_location'],
                    'city' => $pData['city'],
                    'age' => $pData['age'],
                    'price_per_day' => $pData['price_per_day'],
                    'average_rating' => $pData['average_rating'],
                    'accepted_sizes' => $pData['accepted_sizes'],
                    'sex' => $pData['sex'],
                    'accepted_animal_types' => $pData['accepted_animal_types'],
                    'identification_documents' => $pData['identification_documents'],
                    'approval_status' => $pData['approval_status'],
                    'availability_days' => $pData['availability_days'],
                    'profile_photo' => $pData['profile_photo'],
                ]);
            }
        }

        // Export Animals (10 animals)
        Animal::query()->delete();
        $animals = array (
  0 => 
  array (
    'user_id' => 1,
    'name' => 'Pet de Sofia Mendes',
    'species' => 'Dog',
    'breed' => 'Labrador',
    'size' => 'Medium',
    'sex' => 'macho',
    'weight' => '25.50',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  1 => 
  array (
    'user_id' => 2,
    'name' => 'Pet de Tiago Ferreira',
    'species' => 'Dog',
    'breed' => 'Labrador',
    'size' => 'Medium',
    'sex' => 'macho',
    'weight' => '25.50',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  2 => 
  array (
    'user_id' => 3,
    'name' => 'Pet de João Santos',
    'species' => 'Dog',
    'breed' => 'Labrador',
    'size' => 'Medium',
    'sex' => 'macho',
    'weight' => '25.50',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  3 => 
  array (
    'user_id' => 4,
    'name' => 'Pet de Inês Rodrigues',
    'species' => 'Dog',
    'breed' => 'Labrador',
    'size' => 'Medium',
    'sex' => 'macho',
    'weight' => '25.50',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  4 => 
  array (
    'user_id' => 5,
    'name' => 'Pet de Maria Oliveira',
    'species' => 'Dog',
    'breed' => 'Labrador',
    'size' => 'Medium',
    'sex' => 'macho',
    'weight' => '25.50',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  5 => 
  array (
    'user_id' => 6,
    'name' => 'Pet de Rui Costa',
    'species' => 'Dog',
    'breed' => 'Labrador',
    'size' => 'Medium',
    'sex' => 'macho',
    'weight' => '25.50',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  6 => 
  array (
    'user_id' => 7,
    'name' => 'Pet do Admin',
    'species' => 'Dog',
    'breed' => 'Golden Retriever',
    'size' => 'Large',
    'sex' => 'macho',
    'weight' => '30.00',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  7 => 
  array (
    'user_id' => 10,
    'name' => 'Rex',
    'species' => 'Dog',
    'breed' => 'Golden Retriever',
    'size' => 'Large',
    'sex' => 'Macho',
    'weight' => '18.00',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  8 => 
  array (
    'user_id' => 11,
    'name' => 'Luna',
    'species' => 'Cat',
    'breed' => 'Siamês',
    'size' => 'Small',
    'sex' => 'Fêmea',
    'weight' => '18.00',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
  9 => 
  array (
    'user_id' => 12,
    'name' => 'Thor',
    'species' => 'Dog',
    'breed' => 'Labrador',
    'size' => 'Medium',
    'sex' => 'Macho',
    'weight' => '29.00',
    'photo' => NULL,
    'special_needs' => NULL,
    'aggression' => NULL,
    'medication' => NULL,
  ),
);
        foreach ($animals as $aData) {
            $animalData = $aData;
            unset($animalData['id']);
            Animal::create($animalData);
        }

        // Export Posts (13 posts)
        Post::query()->delete();
        $posts = array (
  0 => 
  array (
    'petsitter_id' => 1,
    'title' => 'Passeio matinal com o Thor',
    'description' => 'O Thor é um Labrador cheio de energia! Fizemos um passeio matinal pelo parque e ele fez novos amigos.',
    'photos' => NULL,
  ),
  1 => 
  array (
    'petsitter_id' => 2,
    'title' => 'Aventuras com o Bob',
    'description' => 'O Bob é um cão curioso e aventureiro. Exploramos uma trilha nova hoje e ele descobriu muitos cheiros interessantes.',
    'photos' => NULL,
  ),
  2 => 
  array (
    'petsitter_id' => 2,
    'title' => 'Dia especial com o Max',
    'description' => 'O Max é um Bulldog francês adorável. Fizemos um piquenique no parque e ele comeu muito bem!',
    'photos' => NULL,
  ),
  3 => 
  array (
    'petsitter_id' => 3,
    'title' => 'Tempo de brincadeira com a Pipa',
    'description' => 'A Pipa é uma papagaia inteligente e divertida. Passamos o dia treinando novos truques e ela aprendeu rápido!',
    'photos' => NULL,
  ),
  4 => 
  array (
    'petsitter_id' => 3,
    'title' => 'Dia especial com o Max',
    'description' => 'O Max é um Bulldog francês adorável. Fizemos um piquenique no parque e ele comeu muito bem!',
    'photos' => NULL,
  ),
  5 => 
  array (
    'petsitter_id' => 3,
    'title' => 'Cuidando do Simba',
    'description' => 'O Simba é um gato persa que adora ser mimado. Passamos o dia inteiro brincando e fazendo spa.',
    'photos' => NULL,
  ),
  6 => 
  array (
    'petsitter_id' => 4,
    'title' => 'Conheçam o Rex!',
    'description' => 'Hoje passei o dia com o Rex, um Golden Retriever adorável. Fizemos um passeio pela praia e ele se divertiu muito!',
    'photos' => NULL,
  ),
  7 => 
  array (
    'petsitter_id' => 5,
    'title' => 'Cuidando da Mimi',
    'description' => 'A Mimi é uma gatinha especial que precisa de cuidados atenciosos. Estou a adorando cuidar dela!',
    'photos' => NULL,
  ),
  8 => 
  array (
    'petsitter_id' => 5,
    'title' => 'Dia especial com o Max',
    'description' => 'O Max é um Bulldog francês adorável. Fizemos um piquenique no parque e ele comeu muito bem!',
    'photos' => NULL,
  ),
  9 => 
  array (
    'petsitter_id' => 5,
    'title' => 'Cuidando do Simba',
    'description' => 'O Simba é um gato persa que adora ser mimado. Passamos o dia inteiro brincando e fazendo spa.',
    'photos' => NULL,
  ),
  10 => 
  array (
    'petsitter_id' => 6,
    'title' => 'Tempo de brincadeira com a Pipa',
    'description' => 'A Pipa é uma papagaia inteligente e divertida. Passamos o dia treinando novos truques e ela aprendeu rápido!',
    'photos' => NULL,
  ),
  11 => 
  array (
    'petsitter_id' => 6,
    'title' => 'Dia especial com o Max',
    'description' => 'O Max é um Bulldog francês adorável. Fizemos um piquenique no parque e ele comeu muito bem!',
    'photos' => NULL,
  ),
  12 => 
  array (
    'petsitter_id' => 7,
    'title' => 'Dia especial com o Max',
    'description' => 'O Max é um Bulldog francês adorável. Fizemos um piquenique no parque e ele comeu muito bem!',
    'photos' => NULL,
  ),
);
        foreach ($posts as $postData) {
            $postCreateData = $postData;
            unset($postCreateData['id']);
            Post::create($postCreateData);
        }

        // Export Bookings (7 bookings)
        Booking::query()->delete();
        $bookings = array (
  0 => 
  array (
    'petsitter_id' => 1,
    'user_id' => 10,
    'start_date' => '2026-10-22',
    'end_date' => '2026-10-27',
    'status' => 'completed',
    'total_price' => 60,
  ),
  1 => 
  array (
    'petsitter_id' => 2,
    'user_id' => 11,
    'start_date' => '2026-10-28',
    'end_date' => '2026-10-30',
    'status' => 'completed',
    'total_price' => 20,
  ),
  2 => 
  array (
    'petsitter_id' => 3,
    'user_id' => 12,
    'start_date' => '2026-10-26',
    'end_date' => '2026-10-31',
    'status' => 'completed',
    'total_price' => 55,
  ),
  3 => 
  array (
    'petsitter_id' => 4,
    'user_id' => 10,
    'start_date' => '2026-11-03',
    'end_date' => '2026-11-06',
    'status' => 'completed',
    'total_price' => 27,
  ),
  4 => 
  array (
    'petsitter_id' => 5,
    'user_id' => 11,
    'start_date' => '2026-10-27',
    'end_date' => '2026-11-01',
    'status' => 'completed',
    'total_price' => 65,
  ),
  5 => 
  array (
    'petsitter_id' => 6,
    'user_id' => 12,
    'start_date' => '2026-10-16',
    'end_date' => '2026-10-18',
    'status' => 'completed',
    'total_price' => 20,
  ),
  6 => 
  array (
    'petsitter_id' => 7,
    'user_id' => 10,
    'start_date' => '2026-10-24',
    'end_date' => '2026-10-29',
    'status' => 'completed',
    'total_price' => 75,
  ),
);
        foreach ($bookings as $bookingData) {
            $bookingCreateData = $bookingData;
            unset($bookingCreateData['id']);
            Booking::create($bookingCreateData);
        }

        // Export Reviews (7 reviews)
        Review::query()->delete();
        $reviews = array (
  0 => 
  array (
    'booking_id' => 1,
    'petsitter_id' => 1,
    'star_rating' => 5,
    'comment' => 'Excelente serviço! O meu animal foi muito bem cuidado. Recomendo!',
    'visible' => true,
  ),
  1 => 
  array (
    'booking_id' => 2,
    'petsitter_id' => 2,
    'star_rating' => 5,
    'comment' => 'Muito atencioso e responsável. O meu cão adorou!',
    'visible' => true,
  ),
  2 => 
  array (
    'booking_id' => 3,
    'petsitter_id' => 3,
    'star_rating' => 4,
    'comment' => 'Profissional e carinhoso. Voltarei a contratar!',
    'visible' => true,
  ),
  3 => 
  array (
    'booking_id' => 4,
    'petsitter_id' => 4,
    'star_rating' => 5,
    'comment' => 'Ótima experiência! O petsitter foi pontual e muito cuidado.',
    'visible' => true,
  ),
  4 => 
  array (
    'booking_id' => 5,
    'petsitter_id' => 5,
    'star_rating' => 4,
    'comment' => 'O meu gato foi muito bem cuidado. Muito obrigado!',
    'visible' => true,
  ),
  5 => 
  array (
    'booking_id' => 6,
    'petsitter_id' => 6,
    'star_rating' => 5,
    'comment' => 'Excelente serviço! O meu animal foi muito bem cuidado. Recomendo!',
    'visible' => true,
  ),
  6 => 
  array (
    'booking_id' => 7,
    'petsitter_id' => 7,
    'star_rating' => 4,
    'comment' => 'Muito atencioso e responsável. O meu cão adorou!',
    'visible' => true,
  ),
);
        foreach ($reviews as $reviewData) {
            $reviewCreateData = $reviewData;
            unset($reviewCreateData['id']);
            Review::create($reviewCreateData);
        }

        // Export Animal Bookings (7 animal bookings)
        AnimalBooking::query()->delete();
        $animalBookings = array (
  0 => 
  array (
    'booking_id' => 1,
    'animal_id' => 8,
  ),
  1 => 
  array (
    'booking_id' => 2,
    'animal_id' => 9,
  ),
  2 => 
  array (
    'booking_id' => 3,
    'animal_id' => 10,
  ),
  3 => 
  array (
    'booking_id' => 4,
    'animal_id' => 8,
  ),
  4 => 
  array (
    'booking_id' => 5,
    'animal_id' => 9,
  ),
  5 => 
  array (
    'booking_id' => 6,
    'animal_id' => 10,
  ),
  6 => 
  array (
    'booking_id' => 7,
    'animal_id' => 8,
  ),
);
        foreach ($animalBookings as $abData) {
            $abCreateData = $abData;
            unset($abCreateData['id']);
            AnimalBooking::create($abCreateData);
        }

        \Illuminate\Support\Facades\Event::dispatch(null);
    }
}
