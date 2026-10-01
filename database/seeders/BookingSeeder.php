<?php

namespace Database\Seeders;

use App\Enums\DealType;
use App\Enums\GigStatus;
use App\Enums\InteractionType;
use App\Enums\VenueType;
use App\Models\Contact;
use App\Models\Gig;
use App\Models\Tour;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Données de démo réalistes pour un groupe punk/rock DIY qui tourne dans l'Ouest.
 *
 * Toutes les dates sont calculées par rapport à aujourd'hui : le dashboard
 * reste vivant quel que soit le jour où tu lances le seeder (dates passées
 * jouées, dates confirmées à venir, relances en retard, etc.).
 *
 * Les lieux et les personnes sont inventés.
 */
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        if (Venue::query()->exists()) {
            $this->command?->warn('Des lieux existent déjà : BookingSeeder ignoré (lance migrate:fresh --seed pour repartir de zéro).');

            return;
        }

        $venues = $this->seedVenues();
        $contacts = $this->seedContacts($venues);
        [$tourOuest, $tourPrintemps, $ouestStart, $printempsStart] = $this->seedTours();
        $this->seedGigs($venues, $contacts, $tourOuest, $tourPrintemps, $ouestStart, $printempsStart);
        $this->seedUnavailabilities();
    }

    // -------------------------------------------------------------------------
    //  Lieux
    // -------------------------------------------------------------------------

    /** @return array<string, Venue> */
    private function seedVenues(): array
    {
        $data = [
            'nenette' => [
                'name' => 'Le Bar à Nénette', 'type' => VenueType::Bar, 'city' => 'Nantes',
                'address' => '14 rue des Olivettes', 'capacity' => 70,
                'notes' => 'Petite scène au fond, sono du bar (2 retours). Concerts le jeudi et le samedi, fin à 23h30 max (voisins).',
            ],
            'fabrique' => [
                'name' => 'La Fabrique à Bruit', 'type' => VenueType::Club, 'city' => 'Nantes',
                'address' => 'Quai des Antilles', 'capacity' => 350,
                'website' => 'https://fabrique-a-bruit.test',
                'notes' => 'Vraie salle avec technicien son et lumière. Programme 3 à 4 mois à l\'avance, privilégie les premières parties.',
            ],
            'tanneurs' => [
                'name' => 'Le Squat des Tanneurs', 'type' => VenueType::Squat, 'city' => 'Rennes',
                'address' => 'Rue des Tanneurs', 'capacity' => 150,
                'notes' => 'Prix libre, cantine vegan sur place. Ramener sa backline. Dodo sur place possible (prévoir duvets).',
            ],
            'ecurie' => [
                'name' => 'L\'Écurie', 'type' => VenueType::Bar, 'city' => 'Rennes',
                'address' => 'Place Sainte-Anne', 'capacity' => 90,
                'notes' => 'Bar programmé par le collectif des Tanneurs. Plateau de 3 groupes max.',
            ],
            'cale_seche' => [
                'name' => 'La Cale Sèche', 'type' => VenueType::Club, 'city' => 'Brest',
                'address' => 'Port de commerce', 'capacity' => 250,
                'website' => 'https://lacaleseche.test',
                'notes' => 'Super accueil, repas chaud et hébergement chez les bénévoles.',
            ],
            'ferraille' => [
                'name' => 'L\'Atelier Ferraille', 'type' => VenueType::Squat, 'city' => 'Bordeaux',
                'address' => 'Quartier Belcier', 'capacity' => 200,
                'notes' => 'Très demandé, planning plein longtemps à l\'avance.',
            ],
            'rade' => [
                'name' => 'Le Rade du Port', 'type' => VenueType::Bar, 'city' => 'La Rochelle',
                'address' => 'Quai Duperré', 'capacity' => 60,
                'notes' => 'Bar de marins, ambiance garantie. Pas de sono : ramener la nôtre.',
            ],
            'saint_aubin' => [
                'name' => 'Salle des fêtes de Saint-Aubin', 'type' => VenueType::Association, 'city' => 'Angers',
                'address' => 'Place de la Mairie', 'capacity' => 300,
                'notes' => 'Soirée annuelle organisée par l\'asso Saint-Aubin Rock. 4 groupes, buvette, public familial.',
            ],
            'pied_de_biche' => [
                'name' => 'Le Pied de Biche', 'type' => VenueType::Bar, 'city' => 'Angers',
                'address' => 'Rue Saint-Laud', 'capacity' => 80,
                'notes' => 'Programmé aussi par Saint-Aubin Rock.',
            ],
            'rotonde' => [
                'name' => 'La Rotonde Électrique', 'type' => VenueType::Club, 'city' => 'Lyon',
                'address' => 'Quai Perrache', 'capacity' => 400,
                'website' => 'https://rotonde-electrique.test',
                'notes' => 'Grosse salle, contrat écrit obligatoire et fiche technique demandée 1 mois avant.',
            ],
            'rouille' => [
                'name' => 'Le Local du Collectif Rouille', 'type' => VenueType::Squat, 'city' => 'Lille',
                'address' => 'Quartier Wazemmes', 'capacity' => 120,
                'notes' => 'Collectif hardcore/punk, réponses lentes mais fiables.',
            ],
            'festouest' => [
                'name' => 'Fest\'Ouest Chaos', 'type' => VenueType::Festival, 'city' => 'Saint-Brieuc',
                'address' => 'Parc des expositions', 'capacity' => 2500,
                'website' => 'https://festouest-chaos.test',
                'notes' => 'Festival fin juin. Appel à candidatures de novembre à janvier.',
            ],
            'trou_noir' => [
                'name' => 'Le Trou Noir', 'type' => VenueType::Bar, 'city' => 'Paris',
                'address' => 'Rue Oberkampf', 'capacity' => 100,
                'notes' => 'Cave en sous-sol, son compliqué. Bonne visibilité sur la scène parisienne.',
            ],
            'grange' => [
                'name' => 'La Grange à Larsen', 'type' => VenueType::Association, 'city' => 'Tours',
                'address' => 'Lieu-dit La Bruyère, Joué-lès-Tours', 'capacity' => 200,
                'notes' => 'Asso de bénévoles en pleine campagne, accueil aux petits oignons.',
            ],
        ];

        return array_map(fn (array $attributes) => Venue::create($attributes), $data);
    }

    // -------------------------------------------------------------------------
    //  Contacts (un contact peut programmer plusieurs lieux)
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, Venue>  $venues
     * @return array<string, Contact>
     */
    private function seedContacts(array $venues): array
    {
        $data = [
            'nadia' => [['name' => 'Nadia Benali', 'role' => 'Gérante', 'organization' => 'Le Bar à Nénette', 'email' => 'nadia@bar-nenette.test'], ['nenette']],
            'julien' => [['name' => 'Julien Morvan', 'role' => 'Programmateur', 'organization' => 'La Fabrique à Bruit', 'email' => 'prog@fabrique-a-bruit.test'], ['fabrique']],
            'kevin' => [['name' => 'Kévin Le Goff', 'role' => 'Membre du collectif', 'organization' => 'Collectif des Tanneurs', 'email' => 'tanneurs@riseup.test', 'notes' => 'Préfère Signal aux mails.'], ['tanneurs', 'ecurie']],
            'gwen' => [['name' => 'Gwenaëlle Le Bihan', 'role' => 'Chargée de prog', 'organization' => 'La Cale Sèche', 'email' => 'gwen@lacaleseche.test'], ['cale_seche']],
            'sam' => [['name' => 'Sam Dufour', 'role' => 'Orga concerts', 'organization' => 'Atelier Ferraille', 'email' => 'concerts@atelier-ferraille.test'], ['ferraille']],
            'patrick' => [['name' => 'Patrick Rousseau', 'role' => 'Patron', 'organization' => 'Le Rade du Port', 'email' => null, 'notes' => 'Pas de mail, uniquement par téléphone, plutôt l\'après-midi.'], ['rade']],
            'mathilde' => [['name' => 'Mathilde Chevalier', 'role' => 'Présidente', 'organization' => 'Saint-Aubin Rock', 'email' => 'contact@saintaubinrock.test'], ['saint_aubin', 'pied_de_biche']],
            'elodie' => [['name' => 'Élodie Garnier', 'role' => 'Chargée de programmation', 'organization' => 'La Rotonde Électrique', 'email' => 'e.garnier@rotonde-electrique.test'], ['rotonde']],
            'baptiste' => [['name' => 'Baptiste Lemaire', 'role' => 'Membre du collectif', 'organization' => 'Collectif Rouille', 'email' => 'collectif.rouille@riseup.test'], ['rouille']],
            'yann' => [['name' => 'Yann Kerhervé', 'role' => 'Programmateur', 'organization' => 'Fest\'Ouest Chaos', 'email' => 'prog@festouest-chaos.test'], ['festouest']],
            'ines' => [['name' => 'Inès Moreau', 'role' => 'Barmaid / booking', 'organization' => 'Le Trou Noir', 'email' => 'booking@letrounoir.test'], ['trou_noir']],
            'fred' => [['name' => 'Fred Aubert', 'role' => 'Bénévole prog', 'organization' => 'La Grange à Larsen', 'email' => 'grange.larsen@asso.test'], ['grange']],
        ];

        $contacts = [];

        foreach ($data as $key => [$attributes, $venueKeys]) {
            $contact = Contact::create($attributes + ['phone' => fake('fr_FR')->mobileNumber()]);
            $contact->venues()->attach(array_map(fn (string $k) => $venues[$k]->id, $venueKeys));
            $contacts[$key] = $contact;
        }

        return $contacts;
    }

    // -------------------------------------------------------------------------
    //  Tournées
    // -------------------------------------------------------------------------

    private function seedTours(): array
    {
        $ouestStart = $this->day(-152, Carbon::FRIDAY);
        $printempsStart = $this->day(150, Carbon::THURSDAY);

        $tourOuest = Tour::create([
            'name' => 'Tournée Bretagne',
            'starts_at' => $ouestStart,
            'ends_at' => $ouestStart->copy()->addDays(2),
            'notes' => '3 dates en 3 jours, camion de Tom. Bilan : bon accueil partout, ventes de merch correctes.',
        ]);

        $tourPrintemps = Tour::create([
            'name' => 'Tournée printemps',
            'starts_at' => $printempsStart,
            'ends_at' => $printempsStart->copy()->addDays(9),
            'notes' => 'Objectif : 5-6 dates sur 10 jours, boucle Lyon → Lille → Paris → Tours. Location de van à prévoir.',
        ]);

        return [$tourOuest, $tourPrintemps, $ouestStart, $printempsStart];
    }

    // -------------------------------------------------------------------------
    //  Dates de concert + historique des échanges
    // -------------------------------------------------------------------------

    private function seedGigs(array $v, array $c, Tour $tourOuest, Tour $tourPrintemps, Carbon $ouestStart, Carbon $printempsStart): void
    {
        $gigs = [
            // ---- Passé : joués ----------------------------------------------
            [
                'venue' => 'nenette', 'contact' => 'nadia',
                'date' => $this->day(-240), 'status' => GigStatus::Played,
                'deal_type' => DealType::Hat, 'meals' => true,
                'set_time' => '21:30', 'set_duration' => 40,
                'notes' => 'Première date du groupe. Bar plein, 180 € au chapeau.',
                'interactions' => [
                    [-275, InteractionType::Email, 'Premier mail de présentation avec la démo Bandcamp.'],
                    [-268, InteractionType::Call, 'Nadia partante pour un samedi, plateau avec un groupe local.'],
                    [-262, InteractionType::Email, 'Date confirmée par mail, envoi de l\'affiche.'],
                ],
            ],
            [
                'venue' => 'tanneurs', 'contact' => 'kevin', 'tour' => $tourOuest,
                'date' => $ouestStart->copy(), 'status' => GigStatus::Played,
                'deal_type' => DealType::Free, 'travel_costs' => 60, 'accommodation' => true, 'meals' => true,
                'load_in_at' => '18:00', 'set_time' => '22:15', 'set_duration' => 35,
                'notes' => 'Concert de soutien. Dodo sur place, super soirée.',
                'interactions' => [
                    [-210, InteractionType::Message, 'Message Signal à Kévin pour une date fin de printemps.'],
                    [-196, InteractionType::Message, 'OK pour le vendredi, propose aussi L\'Écurie le lendemain.'],
                    [-160, InteractionType::Message, 'Confirmation horaires et cantine.'],
                ],
            ],
            [
                'venue' => 'ecurie', 'contact' => 'kevin', 'tour' => $tourOuest,
                'date' => $ouestStart->copy()->addDay(), 'status' => GigStatus::Played,
                'deal_type' => DealType::Hat,
                'set_time' => '21:00', 'set_duration' => 40,
                'notes' => 'Moins de monde que la veille (match de foot). 95 € au chapeau.',
                'interactions' => [
                    [-196, InteractionType::Message, 'Date calée en même temps que les Tanneurs.'],
                ],
            ],
            [
                'venue' => 'cale_seche', 'contact' => 'gwen', 'tour' => $tourOuest,
                'date' => $ouestStart->copy()->addDays(2), 'status' => GigStatus::Played,
                'deal_type' => DealType::Fixed, 'fee' => 250, 'travel_costs' => 80, 'accommodation' => true, 'meals' => true,
                'load_in_at' => '17:00', 'set_time' => '20:45', 'set_duration' => 45,
                'notes' => 'Meilleure date de la tournée. Gwen veut nous refaire l\'an prochain.',
                'interactions' => [
                    [-205, InteractionType::Email, 'Candidature envoyée (dossier + live vidéo).'],
                    [-190, InteractionType::Email, 'Relance.'],
                    [-184, InteractionType::Call, 'Gwen intéressée, propose 250 € + défraiement + hébergement.'],
                    [-178, InteractionType::Email, 'Contrat de cession reçu et signé.'],
                ],
            ],
            [
                'venue' => 'fabrique', 'contact' => 'julien',
                'date' => $this->day(-45, Carbon::FRIDAY), 'status' => GigStatus::Played,
                'deal_type' => DealType::Fixed, 'fee' => 300,
                'load_in_at' => '17:30', 'set_time' => '20:00', 'set_duration' => 30,
                'notes' => 'Première partie d\'un groupe US en tournée. Super son, 30 min chrono.',
                'interactions' => [
                    [-120, InteractionType::Email, 'Envoi du dossier pour une première partie.'],
                    [-98, InteractionType::Email, 'Relance polie.'],
                    [-90, InteractionType::Email, 'Julien propose la première partie, cachet 300 €.'],
                    [-60, InteractionType::Email, 'Fiche technique et horaires envoyés.'],
                ],
            ],

            // ---- Passé : refusé / annulé ------------------------------------
            [
                'venue' => 'ferraille', 'contact' => 'sam',
                'date' => null, 'status' => GigStatus::Declined,
                'notes' => 'Planning plein, recontacter pour l\'automne prochain.',
                'interactions' => [
                    [-130, InteractionType::Email, 'Demande de date pour la rentrée.'],
                    [-112, InteractionType::Email, 'Relance.'],
                    [-108, InteractionType::Email, 'Sam répond : plus aucun créneau avant l\'an prochain, à recontacter.'],
                ],
            ],
            [
                'venue' => 'rade', 'contact' => 'patrick',
                'date' => $this->day(-20), 'status' => GigStatus::Cancelled,
                'deal_type' => DealType::Hat, 'travel_costs' => 70,
                'notes' => 'Annulé 5 jours avant : dégât des eaux, bar fermé. Patrick propose de reporter.',
                'interactions' => [
                    [-70, InteractionType::Call, 'Patrick OK pour un samedi, au chapeau + essence.'],
                    [-25, InteractionType::Call, 'Patrick appelle : dégât des eaux, bar fermé, on annule.'],
                ],
            ],

            // ---- À venir : confirmé -----------------------------------------
            [
                'venue' => 'pied_de_biche', 'contact' => 'mathilde',
                'date' => $this->day(16), 'status' => GigStatus::Confirmed,
                'deal_type' => DealType::Hat, 'travel_costs' => 40, 'meals' => true,
                'load_in_at' => '19:00', 'set_time' => '21:30', 'set_duration' => 40,
                'notes' => 'Plateau avec un groupe local angevin.',
                'interactions' => [
                    [-40, InteractionType::Email, 'Mathilde nous propose une date au Pied de Biche.'],
                    [-33, InteractionType::Email, 'Date confirmée, affiche à envoyer 3 semaines avant.'],
                ],
            ],
            [
                'venue' => 'saint_aubin', 'contact' => 'mathilde',
                'date' => $this->day(37), 'status' => GigStatus::Confirmed,
                'deal_type' => DealType::Fixed, 'fee' => 200, 'travel_costs' => 60, 'accommodation' => true, 'meals' => true,
                'load_in_at' => '16:00', 'set_time' => '22:00', 'set_duration' => 45,
                'notes' => 'Soirée annuelle de l\'asso, on joue en 3e position sur 4. Backline partagée.',
                'interactions' => [
                    [-90, InteractionType::Meeting, 'Rencontre avec Mathilde au concert de la Fabrique, intéressée.'],
                    [-75, InteractionType::Email, 'Proposition : 200 € + défraiement + hébergement.'],
                    [-70, InteractionType::Email, 'Accepté, convention envoyée par l\'asso.'],
                ],
            ],

            // ---- À venir : en cours de prospection --------------------------
            [
                'venue' => 'nenette', 'contact' => 'nadia',
                'date' => $this->day(60), 'status' => GigStatus::Discussing,
                'deal_type' => DealType::Hat,
                'next_follow_up_at' => today()->addDays(7),
                'notes' => 'Nadia veut nous refaire, hésite entre deux samedis.',
                'interactions' => [
                    [-12, InteractionType::Message, 'SMS à Nadia pour une nouvelle date.'],
                    [-9, InteractionType::Call, 'Partante, doit vérifier son planning et revenir vers nous.'],
                ],
            ],
            [
                'venue' => 'fabrique', 'contact' => 'julien',
                'date' => null, 'status' => GigStatus::Prospect,
                'next_follow_up_at' => today()->subDay(),
                'notes' => 'Proposer une tête d\'affiche locale maintenant qu\'on a fait la première partie.',
            ],
            [
                'venue' => 'rotonde', 'contact' => 'elodie', 'tour' => $tourPrintemps,
                'date' => $printempsStart->copy(), 'status' => GigStatus::Option,
                'deal_type' => DealType::DoorDeal,
                'next_follow_up_at' => today()->addDays(10),
                'notes' => 'Option posée, 60 % des entrées après frais. Contrat à recevoir.',
                'interactions' => [
                    [-35, InteractionType::Email, 'Candidature pour la tournée de printemps.'],
                    [-21, InteractionType::Email, 'Relance avec le nouveau clip.'],
                    [-15, InteractionType::Call, 'Élodie pose une option, confirmation définitive sous 3 semaines.'],
                ],
            ],
            [
                'venue' => 'rouille', 'contact' => 'baptiste', 'tour' => $tourPrintemps,
                'date' => $printempsStart->copy()->addDays(2), 'status' => GigStatus::Discussing,
                'deal_type' => DealType::Free, 'travel_costs' => 120, 'accommodation' => true,
                'next_follow_up_at' => today()->subDays(2),
                'notes' => 'Prix libre, ils cherchent un 2e groupe pour compléter.',
                'interactions' => [
                    [-28, InteractionType::Email, 'Mail au collectif pour une date au printemps.'],
                    [-16, InteractionType::Email, 'Baptiste chaud, doit valider en assemblée du collectif.'],
                ],
            ],
            [
                'venue' => 'trou_noir', 'contact' => 'ines', 'tour' => $tourPrintemps,
                'date' => $printempsStart->copy()->addDays(3), 'status' => GigStatus::Contacted,
                'deal_type' => DealType::Hat,
                'next_follow_up_at' => today()->subDays(5),
                'notes' => 'Indispensable pour avoir une date à Paris sur la tournée.',
                'interactions' => [
                    [-19, InteractionType::Email, 'Premier mail avec liens Bandcamp + live.'],
                ],
            ],
            [
                'venue' => 'grange', 'contact' => 'fred', 'tour' => $tourPrintemps,
                'date' => $printempsStart->copy()->addDays(8), 'status' => GigStatus::Option,
                'deal_type' => DealType::Fixed, 'fee' => 180, 'accommodation' => true, 'meals' => true,
                'next_follow_up_at' => today()->addDays(4),
                'notes' => 'Date de clôture de la tournée. Fred attend le vote du CA de l\'asso.',
                'interactions' => [
                    [-24, InteractionType::Email, 'Contact via un ami commun.'],
                    [-11, InteractionType::Call, 'Fred très motivé, option posée en attendant le CA.'],
                ],
            ],
            [
                'venue' => 'festouest', 'contact' => 'yann',
                'date' => $this->day(270), 'status' => GigStatus::Contacted,
                'next_follow_up_at' => today(),
                'notes' => 'Candidature au festival. Réponses en février.',
                'interactions' => [
                    [-6, InteractionType::Email, 'Dossier de candidature envoyé via le formulaire du festival.'],
                ],
            ],
        ];

        foreach ($gigs as $data) {
            $interactions = $data['interactions'] ?? [];
            $contact = $c[$data['contact']];

            $gig = Gig::create([
                'venue_id' => $v[$data['venue']]->id,
                'contact_id' => $contact->id,
                'tour_id' => ($data['tour'] ?? null)?->id,
                'date' => $data['date'],
                'load_in_at' => $data['load_in_at'] ?? null,
                'set_time' => $data['set_time'] ?? null,
                'set_duration' => $data['set_duration'] ?? null,
                'status' => $data['status'],
                'deal_type' => $data['deal_type'] ?? null,
                'fee' => $data['fee'] ?? null,
                'travel_costs' => $data['travel_costs'] ?? null,
                'accommodation' => $data['accommodation'] ?? false,
                'meals' => $data['meals'] ?? false,
                'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($interactions as [$offset, $type, $summary]) {
                $gig->interactions()->create([
                    'contact_id' => $contact->id,
                    'type' => $type,
                    'happened_at' => today()->addDays($offset)->setTime(random_int(9, 22), [0, 15, 30, 45][random_int(0, 3)]),
                    'summary' => $summary,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    //  Membres du groupe et indispos
    // -------------------------------------------------------------------------

    private function seedUnavailabilities(): void
    {
        $members = collect([
            ['name' => 'Tom', 'email' => 'tom@groupe.test'],
            ['name' => 'Julie', 'email' => 'julie@groupe.test'],
            ['name' => 'Max', 'email' => 'max@groupe.test'],
            ['name' => 'Léa', 'email' => 'lea@groupe.test'],
        ])->mapWithKeys(fn (array $m) => [
            $m['name'] => User::firstOrCreate(['email' => $m['email']], $m + ['password' => 'password']),
        ]);

        $unavailabilities = [
            ['Tom', 25, 26, 'Mariage de sa sœur'],
            ['Julie', 80, 94, 'Vacances'],
            ['Max', 45, 45, 'Astreinte boulot'],
            ['Léa', 158, 161, 'Stage pro (pendant la tournée !)'],
        ];

        foreach ($unavailabilities as [$name, $from, $to, $reason]) {
            Unavailability::create([
                'user_id' => $members[$name]->id,
                'starts_at' => today()->addDays($from),
                'ends_at' => today()->addDays($to),
                'reason' => $reason,
            ]);
        }
    }

    // -------------------------------------------------------------------------

    /** Le premier jour donné (samedi par défaut) à partir de today() + $offset. */
    private function day(int $offset, int $weekday = Carbon::SATURDAY): Carbon
    {
        $date = today()->addDays($offset);

        return $date->dayOfWeek === $weekday ? $date : $date->next($weekday);
    }
}
