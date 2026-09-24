<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    // --- 読み取り系(FN058, FN059) ---

    public function test_getで勤怠一覧がjsonで取得できる(): void
    {
        AttendanceRecord::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_getで勤怠詳細がjsonで取得できる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        BreakTime::factory()->for($record)->create();
        AttendanceCorrectRequest::factory()->for($record)->create();

        $response = $this->getJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonCount(1, 'data.breaks');
        $response->assertJsonCount(1, 'data.applications');
    }

    public function test_存在しないidでは404が返る(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404);
        $response->assertExactJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    // --- 書き込み系(FN060, FN061, FN062) ---

    public function test_postで勤怠が作成される(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-05-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'テスト備考',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.date', '2026-05-01');
        $response->assertJsonPath('data.clock_in', '09:00:00');
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'comment' => 'テスト備考',
        ]);
    }

    public function test_バリデーションエラー時に422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertStatus(422);
        $response->assertJsonFragment(['date' => ['勤怠日は必須です。']]);
        $response->assertJsonFragment(['clock_in' => ['出勤時刻は必須です。']]);
    }

    public function test_putで勤怠が更新される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create(['comment' => '元の備考']);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/attendance-records/{$record->id}", [
            'comment' => '更新後の備考',
        ]);

        $response->assertStatus(200);
        $this->assertSame('更新後の備考', $record->fresh()->comment);
    }

    public function test_存在しないidへのputは404が返る(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/attendance-records/99999', [
            'comment' => '更新後の備考',
        ]);

        $response->assertStatus(404);
    }

    public function test_deleteで勤怠が削除される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
    }

    public function test_存在しないidへのdeleteは404が返る(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404);
    }

    // --- Sanctum認証・認可(FN066, FN067, FN068) ---

    public function test_未認証でpostすると401が返る(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertStatus(401);
        $response->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_未認証でputすると401が返る(): void
    {
        $record = AttendanceRecord::factory()->create();

        $response = $this->putJson("/api/v1/attendance-records/{$record->id}", []);

        $response->assertStatus(401);
        $response->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_未認証でdeleteすると401が返る(): void
    {
        $record = AttendanceRecord::factory()->create();

        $response = $this->deleteJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(401);
        $response->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_認証済みユーザーは自分の勤怠を更新できる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/attendance-records/{$record->id}", [
            'comment' => '本人による更新',
        ]);

        $response->assertStatus(200);
    }

    public function test_認証済みユーザーは自分の勤怠を削除できる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(204);
    }

    public function test_他ユーザーの勤怠を更新しようとすると403が返る(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = AttendanceRecord::factory()->for($owner)->create();
        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/attendance-records/{$record->id}", [
            'comment' => '不正な更新',
        ]);

        $response->assertStatus(403);
        $response->assertExactJson(['error' => 'この操作を実行する権限がありません。']);
    }

    public function test_他ユーザーの勤怠を削除しようとすると403が返る(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = AttendanceRecord::factory()->for($owner)->create();
        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(403);
        $response->assertExactJson(['error' => 'この操作を実行する権限がありません。']);
    }
}
