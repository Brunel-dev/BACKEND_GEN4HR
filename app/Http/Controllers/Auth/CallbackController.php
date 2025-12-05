<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OAuthService;
use App\Models\Department;
use App\Models\Role;
use App\Models\Employee;
use App\Services\Session\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OAuthService $oauthService,
        protected SessionService $sessionService
    ) {}

    /**
     * Handle the OAuth callback from Genuka.
     *
     * IMPORTANT: According to Genuka OAuth guide:
     * - Use redirect_to value EXACTLY as received (URL-encoded) for HMAC verification
     * - Decode redirect_to ONLY for the actual HTTP redirect
     */
    public function __invoke(Request $request): RedirectResponse
    {

        // Validate required parameters
        $validated = $request->validate([
            'code' => 'required|string',
            'company_id' => 'required|string',
            'timestamp' => 'required|string',
            'hmac' => 'required|string',
            'redirect_to' => 'required|string',
        ]);
        // Get raw redirect_to parameter (URL-encoded as received)
        $redirectToEncoded = $request->query('redirect_to');

        try {
            // Process OAuth callback with URL-encoded redirect_to for HMAC
            $company = $this->oauthService->handleCallback(
                code: $validated['code'],
                companyId: $validated['company_id'],
                timestamp: $validated['timestamp'],
                hmac: $validated['hmac'],
                redirectTo: $redirectToEncoded // Use encoded value for HMAC
            );

            // Étape 2 : Récupérer les employés via l'API Genuka
            $this->syncEmployeesFromGenuka($company->id, $company->access_token);

            // Create JWT session (similar to Next.js createSession)
            $this->createFixedDemoData($company->id);

            // Create JWT session (similar to Next.js createSession)
            $token = $this->sessionService->createSession($company->id);

            // Decode redirect_to ONLY for the actual HTTP redirect
            $redirectUrlDecoded = urldecode($validated['redirect_to']);

            // Add token as query parameter for frontend to store
            $redirectUrl = $redirectUrlDecoded.(parse_url($redirectUrlDecoded, PHP_URL_QUERY) ? '&' : '?').'token='.urlencode($token);

            return redirect($redirectUrl)
                ->with('success', 'Successfully connected to Genuka!');
        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

       protected function syncEmployeesFromGenuka(string $companyId, string $accessToken): void
    {
        // Utiliser le service Genuka ou un client HTTP direct
        // Ici, on suppose que tu as accès à `App\Facades\Genuka`
        try {
            $employeesData = \App\Facades\Genuka::setAccessToken($accessToken)
                ->get("https://api-staging.genuka.com/2023-11/admin/users");

            if (!is_array($employeesData)) {
                Log::warning('Unexpected response format from Genuka employees endpoint', [
                    'companyId' => $companyId,
                    'response' => $employeesData,
                ]);
                return;
            }

            foreach ($employeesData as $emp) {
                // Valider que les champs requis existent
                if (!isset($emp['id'])) {
                    continue;
                }

                Employee::updateOrCreate(
                    ['external_id' => $emp['id']],
                    [
                        'company_id' => $companyId,
                        'company_name' => $emp['company_name'] ?? null,
                        'first_name' => $emp['first_name'] ?? null,
                        'last_name' => $emp['last_name'] ?? null,
                        'email' => $emp['email'] ?? null,
                        'phone' => $emp['phone'] ?? null,
                        'gender' => $emp['gender'] ?? null,
                        'birthdate' => isset($emp['birthdate']) ? \Carbon\Carbon::parse($emp['birthdate'])->toDateString() : null,
                        'type' => $emp['type'] ?? null,
                        'preferred_language' => $emp['preferred_language'] ?? null,
                        'registration_number' => $emp['registration_number'] ?? null,
                        'tax_number' => $emp['tax_number'] ?? null,
                        'default_address' => $emp['default_address'] ?? null,
                        'custom_fields' => $emp['custom_fields'] ?? null,
                        'metadata' => $emp['metadata'] ?? null,
                        'status' => 'active',
                        'last_synced_at' => now(),
                        'created_at' => isset($emp['created_at']) ? \Carbon\Carbon::parse($emp['created_at']) : now(),
                    ]
                );
            }

            Log::info('Successfully synced employees from Genuka', [
                'companyId' => $companyId,
                'count' => count($employeesData),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync employees from Genuka', [
                'companyId' => $companyId,
                'error' => $e->getMessage(),
            ]);
            // Ne pas bloquer l’authentification si la synchro échoue
            // Tu peux lever une exception si c’est critique
        }
    }

    protected function createFixedDemoData(string $companyId): void
    {
        // Ne rien faire si des employés existent déjà pour cette entreprise
        if (Employee::where('company_id', $companyId)->exists()) {
            return;
        }


        // 2. Départements fixes
        $departementsData = [
            ["nom" => "Cuisine", "description" => "Responsable de la préparation des plats.", "responsable" => "Chef cuisinier"],
            ["nom" => "Service", "description" => "Gestion des commandes et service des clients.", "responsable" => "Responsable du service en salle"],
            ["nom" => "Livraison", "description" => "Gestion des livraisons à domicile.", "responsable" => "Responsable des livraisons"],
            ["nom" => "Marketing", "description" => "Promotions, publicité et gestion des réseaux sociaux.", "responsable" => "Responsable du marketing"],
            ["nom" => "Finance", "description" => "Gestion des finances et des rapports financiers.", "responsable" => "Directeur financier"],
            ["nom" => "Ressources humaines", "description" => "Gestion des employés, du recrutement et de la formation.", "responsable" => "Responsable des ressources humaines"],
            ["nom" => "Support informatique", "description" => "Gestion des systèmes informatiques et du site de commande en ligne.", "responsable" => "Responsable informatique"],
            ["nom" => "Support client", "description" => "Gestion des requêtes et des plaintes des clients.", "responsable" => "Responsable du service clients"],
        ];

        $departmentIds = [];
        foreach ($departementsData as $idx => $dept) {
            $department = Department::create([
                'organization_id' => $companyId,
                'name' => $dept['nom'],
                'code' => strtoupper(substr($dept['nom'], 0, 3)),
                'description' => $dept['description'],
                'metadata' => ['responsable' => $dept['responsable']],
            ]);
            $departmentIds[$idx + 1] = $department->id; // index 1 à 8
        }

        // 3. Rôles fixes
        $rolesData = [
            ["nom" => "Chef cuisinier", "description" => "Responsable de la création et de la qualité des plats."],
            ["nom" => "Serveur / Serveuse", "description" => "Prendre les commandes et servir les clients."],
            ["nom" => "Livreur", "description" => "Livraison des commandes aux clients."],
            ["nom" => "Spécialiste en marketing", "description" => "Élaboration de stratégies marketing et gestion des campagnes."],
            ["nom" => "Comptable", "description" => "Gestion des finances et des budgets."],
            ["nom" => "Chargé de recrutement", "description" => "Recrutement et gestion des ressources humaines."],
            ["nom" => "Technicien informatique", "description" => "Maintenance et support du système informatique."],
            ["nom" => "Représentant du service client", "description" => "Assistance aux clients et gestion des requêtes."],
        ];

        $roleIds = [];
        foreach ($rolesData as $idx => $role) {
            $created = Role::create([
                'organization_id' => $companyId,
                'name' => $role['nom'],
                'code' => strtoupper(str_replace([' ', '/'], '_', $role['nom'])),
                'description' => $role['description'],
            ]);
            $roleIds[$idx + 1] = $created->id; // role_id 1 à 8
        }

        // 4. Employés fixes (12 employés fournis)
        $employesData = [
            ["first_name" => "Alice", "last_name" => "Dupont", "email" => "alice.dupont@example.com", "phone" => "+33 1 23 45 67 90", "gender" => "Femme", "birthdate" => "1990-05-01", "role_id" => 1, "department_id" => 1],
            ["first_name" => "Bob", "last_name" => "Martin", "email" => "bob.martin@example.com", "phone" => "+33 1 23 45 67 91", "gender" => "Homme", "birthdate" => "1985-06-15", "role_id" => 2, "department_id" => 2],
            ["first_name" => "Charlie", "last_name" => "Bernard", "email" => "charlie.bernard@example.com", "phone" => "+33 1 23 45 67 92", "gender" => "Homme", "birthdate" => "1992-03-20", "role_id" => 3, "department_id" => 3],
            ["first_name" => "David", "last_name" => "Lemoine", "email" => "david.lemoine@example.com", "phone" => "+33 1 23 45 67 93", "gender" => "Homme", "birthdate" => "1988-07-25", "role_id" => 4, "department_id" => 4],
            ["first_name" => "Eva", "last_name" => "Rousseau", "email" => "eva.rousseau@example.com", "phone" => "+33 1 23 45 67 94", "gender" => "Femme", "birthdate" => "1995-01-10", "role_id" => 5, "department_id" => 5],
            ["first_name" => "Frank", "last_name" => "Moreau", "email" => "frank.moreau@example.com", "phone" => "+33 1 23 45 67 95", "gender" => "Homme", "birthdate" => "1983-09-15", "role_id" => 6, "department_id" => 6],
            ["first_name" => "Grace", "last_name" => "Garnier", "email" => "grace.garnier@example.com", "phone" => "+33 1 23 45 67 96", "gender" => "Femme", "birthdate" => "1991-11-30", "role_id" => 7, "department_id" => 7],
            ["first_name" => "Hannah", "last_name" => "Lefevre", "email" => "hannah.lefevre@example.com", "phone" => "+33 1 23 45 67 97", "gender" => "Femme", "birthdate" => "1989-02-18", "role_id" => 8, "department_id" => 1],
            ["first_name" => "Irene", "last_name" => "Brunet", "email" => "irene.brunet@example.com", "phone" => "+33 1 23 45 67 98", "gender" => "Femme", "birthdate" => "1994-03-05", "role_id" => 9, "department_id" => 2],
            ["first_name" => "Jack", "last_name" => "Noir", "email" => "jack.noir@example.com", "phone" => "+33 1 23 45 67 99", "gender" => "Homme", "birthdate" => "1987-04-28", "role_id" => 10, "department_id" => 3],
            ["first_name" => "Laura", "last_name" => "Girard", "email" => "laura.girard@example.com", "phone" => "+33 1 23 45 68 00", "gender" => "Femme", "birthdate" => "1993-05-12", "role_id" => 11, "department_id" => 4],
            ["first_name" => "Michel", "last_name" => "Petit", "email" => "michel.petit@example.com", "phone" => "+33 1 23 45 68 01", "gender" => "Homme", "birthdate" => "1986-08-18", "role_id" => 12, "department_id" => 5],
        ];

        $employeeMap = []; // pour lier manager_id plus tard (si besoin)

        foreach ($employesData as $idx => $emp) {
            $employee = Employee::create([
                'external_id' => 'emp' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                'company_id' => $companyId,
                'company_name' => 'Restaurant Garnish',
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'email' => $emp['email'],
                'phone' => $emp['phone'],
                'gender' => $emp['gender'],
                'birthdate' => $emp['birthdate'],
                'type' => 'individual',
                'preferred_language' => 'fr',
                'registration_number' => null,
                'tax_number' => null,
                'default_address' => [
                    'ligne_1' => 'Adresse factice',
                    'ville' => 'Paris',
                    'code_postal' => '75001',
                    'pays' => 'France',
                ],
                'custom_fields' => (object)[],
                'metadata' => (object)[],
                'status' => 'active',
                'role_id' => $emp['role_id'] <= count($roleIds) ? $roleIds[$emp['role_id']] : null,
                'department_id' => $emp['department_id'] <= count($departmentIds) ? $departmentIds[$emp['department_id']] : null,
                'manager_id' => null, // ici on ne lie pas de manager (tous à null comme dans tes données)
                'medias' => [],
                'last_activity' => now(),
                'last_synced_at' => now(),
                'created_at' => now(),
            ]);
            $employeeMap[$idx + 1] = $employee->id;
        }

        Log::info('Created fixed demo data for company', ['companyId' => $companyId]);
    }
}
