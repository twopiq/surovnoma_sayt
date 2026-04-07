<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\SlaProfile;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\SlaCalculator;
use App\Services\TicketService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        $departments = collect([
            ['name' => 'Texnik qo‘llab-quvvatlash', 'code' => 'TECH'],
            ['name' => 'LMS xizmatlari', 'code' => 'LMS'],
            ['name' => 'Veb platforma', 'code' => 'WEB'],
            ['name' => 'Raqamli audit va monitoring', 'code' => 'MON'],
        ])->map(fn (array $department) => Department::query()->updateOrCreate(['code' => $department['code']], [
            ...$department,
            'description' => $department['name'],
            'is_active' => true,
        ]));

        $profiles = [
            ['name' => 'Past', 'priority' => TicketPriority::Low->value, 'duration_minutes' => 3 * 9 * 60, 'warning_minutes' => 9 * 60],
            ['name' => 'O‘rta', 'priority' => TicketPriority::Medium->value, 'duration_minutes' => 2 * 9 * 60, 'warning_minutes' => 9 * 60],
            ['name' => 'Yuqori', 'priority' => TicketPriority::High->value, 'duration_minutes' => 9 * 60, 'warning_minutes' => 4 * 60],
            ['name' => 'Shoshilinch', 'priority' => TicketPriority::Urgent->value, 'duration_minutes' => 6 * 60, 'warning_minutes' => 60],
        ];

        foreach ($profiles as $profile) {
            SlaProfile::query()->updateOrCreate(['priority' => $profile['priority']], [
                ...$profile,
                'description' => $profile['name'],
                'is_active' => true,
            ]);
        }

        foreach (app(SlaCalculator::class)->defaultSchedules() as $schedule) {
            WorkSchedule::query()->updateOrCreate(['weekday' => $schedule['weekday']], $schedule);
        }

        $categories = [
            ['name' => 'LMS muammolari', 'slug' => 'lms-issues', 'department_id' => $departments[1]->id, 'default_priority' => TicketPriority::Medium->value],
            ['name' => 'Texnik nosozlik', 'slug' => 'technical-failure', 'department_id' => $departments[0]->id, 'default_priority' => TicketPriority::High->value],
            ['name' => 'Veb platforma', 'slug' => 'web-platform', 'department_id' => $departments[2]->id, 'default_priority' => TicketPriority::Medium->value],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], [
                ...$category,
                'description' => $category['name'],
                'is_active' => true,
            ]);
        }

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@rtt.local',
            'department_id' => $departments[0]->id,
        ]);
        $admin->syncRoles([UserRole::Admin->value]);

        $operator = User::factory()->create([
            'name' => 'Operator User',
            'email' => 'operator@rtt.local',
            'department_id' => $departments[0]->id,
        ]);
        $operator->syncRoles([UserRole::Operator->value]);

        $executor = User::factory()->create([
            'name' => 'Executor User',
            'email' => 'executor@rtt.local',
            'department_id' => $departments[1]->id,
        ]);
        $executor->syncRoles([UserRole::Executor->value]);

        $manager = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@rtt.local',
            'department_id' => $departments[3]->id,
        ]);
        $manager->syncRoles([UserRole::Manager->value]);

        $requester = User::factory()->create([
            'name' => 'Requester User',
            'email' => 'requester@rtt.local',
            'department_id' => $departments[2]->id,
        ]);
        $requester->syncRoles([UserRole::Requester->value]);

        $ticketService = app(TicketService::class);

        [$ticketOne] = $ticketService->create([
            'channel' => 'requester',
            'requester_id' => $requester->id,
            'requester_name' => $requester->name,
            'requester_email' => $requester->email,
            'requester_phone' => $requester->phone,
            'requester_department' => $requester->department?->name,
            'requester_job_title' => $requester->job_title,
            'description' => 'Moodle kursida yakuniy test ochilmayapti va baholash bo‘limi bo‘sh chiqmoqda.',
        ], $requester);

        $ticketService->assign($ticketOne, $admin, $departments[1]->id, $executor->id, TicketPriority::High, Category::query()->where('slug', 'lms-issues')->value('id'));

        [$ticketTwo] = $ticketService->create([
            'channel' => 'operator',
            'operator_id' => $operator->id,
            'requester_name' => 'Dilshod Qodirov',
            'requester_email' => 'dilshod@example.test',
            'requester_phone' => '+998901234567',
            'requester_department' => 'Kafedra',
            'requester_job_title' => 'O‘qituvchi',
            'description' => 'Auditoriyadagi proektor tizimga ulanmayapti va USB chiqishi ishlamayapti.',
        ], $operator);

        $ticketService->assign($ticketTwo, $admin, $departments[0]->id, $executor->id, TicketPriority::Urgent, Category::query()->where('slug', 'technical-failure')->value('id'));

        [$ticketThree] = $ticketService->create([
            'channel' => 'guest',
            'requester_name' => 'Guest foydalanuvchi',
            'requester_email' => 'guest@example.test',
            'requester_phone' => '+998907654321',
            'requester_department' => 'Tashrif buyuruvchi',
            'requester_job_title' => 'Mehmon',
            'description' => 'Institut saytining murojaat yuborish sahifasida fayl yuklash jarayoni xatolik bermoqda.',
        ]);

        $ticketService->assign($ticketThree, $admin, $departments[2]->id, $executor->id, TicketPriority::Medium, Category::query()->where('slug', 'web-platform')->value('id'));
    }
}
