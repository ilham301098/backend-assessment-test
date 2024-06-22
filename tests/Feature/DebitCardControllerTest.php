<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Support\Carbon;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards        
        $user = User::factory()->create();
        $this->actingAs($user);

        $userDebitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)->assertJsonCount(1);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        // Create debit cards for both users
        $userDebitCard = DebitCard::factory()->create(['user_id' => $user->id]);
        $otherUserDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards');

        // Assert that only the debit cards of the authenticated user are returned
        $response->assertStatus(200)
                 ->assertJsonMissing(['id' => $userDebitCard->id])
                 ->assertJsonFragment(['id' => $otherUserDebitCard->id])
                 ->assertJsonCount(1);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/debit-cards', [
            'type' => 'visa',
        ]);

        $response->assertStatus(201)
        ->assertJsonPath('data.type', 'visa')
        ->assertJsonPath('data.user_id', $user->id);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $user = User::factory()->create();
        $this->actingAs($user);

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
        ->assertJsonPath('data.id', $debitCard->id);
    }
    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403); 
    }

    public function testCustomerCanActivateADebitCard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id, 'disabled_at' => Carbon::now()]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(200)
        ->assertJsonPath('data.disabled_at', null);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id, 'disabled_at' => null]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
        ->assertJsonPath('data.disabled_at', now()->toJSON());
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => 'invalid_value', 
        ]);

        $response->assertStatus(422); 
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(400); 

        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

}
