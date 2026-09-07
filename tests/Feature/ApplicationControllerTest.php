<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_承認待ちにログインユーザーが行った申請が全て表示されている(): void
    {
        $user = User::factory()->create();
        $recordA = AttendanceRecord::factory()->for($user)->create(['work_date' => '2026-09-01']);
        $recordB = AttendanceRecord::factory()->for($user)->create(['work_date' => '2026-09-02']);
        AttendanceCorrectRequest::factory()->for($recordA)->create(['comment' => '電車遅延のため']);
        AttendanceCorrectRequest::factory()->for($recordB)->create(['comment' => '体調不良のため']);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertOk();
        $response->assertSee('承認待ち');
        $response->assertSee('電車遅延のため');
        $response->assertSee('体調不良のため');
    }

    public function test_承認済みに管理者が承認した申請が表示される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        AttendanceCorrectRequest::factory()->for($record)->create([
            'comment' => '承認済みの申請',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertSee('承認済み');
        $response->assertSee('承認済みの申請');
    }

    public function test_他人の申請は一覧に表示されない(): void
    {
        $user = User::factory()->create();
        $otherUsersRecord = AttendanceRecord::factory()->create();
        AttendanceCorrectRequest::factory()->for($otherUsersRecord)->create(['comment' => '他人の申請理由']);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertDontSee('他人の申請理由');
    }
}
