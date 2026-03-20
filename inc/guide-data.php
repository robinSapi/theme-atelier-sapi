<?php
/**
 * Guide Data — Questions, choix et icônes du questionnaire
 * Partagé entre le bandeau "Mon projet" (header.php) et les endpoints AJAX.
 *
 * @package Theme_Sapi_Maison
 */

/**
 * Retourne les 7 étapes du questionnaire avec logique de visibilité.
 */
function sapi_guide_get_steps() {
  return [
    [
      'id'         => 'piece',
      'question'   => 'Pour quelle pièce cherchez-vous un luminaire ?',
      'visibility' => 'always',
      'choices'    => [
        ['label' => 'Cuisine',                   'slug' => 'cuisine',  'icon' => 'dining'],
        ['label' => 'Bureau / Atelier',          'slug' => 'bureau',   'icon' => 'monitor'],
        ['label' => 'Salon / Salle à manger',    'slug' => 'salon',    'icon' => 'sofa'],
        ['label' => 'Chambre',                   'slug' => 'chambre',  'icon' => 'bed'],
        ['label' => 'Entrée / Couloir',          'slug' => 'entree',   'icon' => 'door'],
        ['label' => 'Cage d\'escalier',          'slug' => 'escalier', 'icon' => 'stairs'],
      ],
    ],
    [
      'id'         => 'taille',
      'question'   => 'Quelle est la taille de votre pièce ?',
      'visibility' => ['piece' => ['cuisine', 'bureau', 'salon', 'chambre', 'entree']],
      'choices'    => [
        ['label' => 'Petite',   'dim' => '< 10 m²',   'slug' => 'petite',  'icon' => 'square-sm'],
        ['label' => 'Moyenne',  'dim' => '10–20 m²',   'slug' => 'moyenne', 'icon' => 'square-md'],
        ['label' => 'Grande',   'dim' => '> 20 m²',    'slug' => 'grande',  'icon' => 'square-lg'],
      ],
    ],
    [
      'id'         => 'taille_escalier',
      'question'   => 'Quel type d\'escalier ?',
      'visibility' => ['piece' => ['escalier']],
      'choices'    => [
        ['label' => 'Escalier standard',       'slug' => 'standard', 'icon' => 'stairs'],
        ['label' => 'Grand escalier ouvert',   'slug' => 'ouvert',   'icon' => 'square-lg'],
      ],
    ],
    [
      'id'         => 'eclairage',
      'question'   => 'Ce luminaire sera-t-il votre principale source de lumière ?',
      'visibility' => ['taille' => ['grande']],
      'choices'    => [
        ['label' => 'Ce sera la principale source d\'éclairage', 'slug' => 'principal',  'icon' => 'sun'],
        ['label' => 'J\'ai d\'autres sources lumineuses',       'slug' => 'secondaire', 'icon' => 'lamp-desk'],
      ],
    ],
    [
      'id'         => 'sortie',
      'question'   => 'Où installerez-vous votre luminaire ?',
      'visibility' => ['_or' => [
        ['taille' => ['petite', 'moyenne']],
        ['eclairage' => ['principal', 'secondaire']],
        ['piece' => ['escalier']],
      ]],
      'choices'    => [
        ['label' => 'Au plafond',               'slug' => 'plafond',       'icon' => 'ceiling-plug'],
        ['label' => 'Au mur',                   'slug' => 'mur',           'icon' => 'wall-plug'],
        ['label' => 'Sur prise classique 230V', 'slug' => 'pas-de-sortie', 'icon' => 'no-plug'],
        ['label' => 'Je ne sais pas',           'slug' => 'ne-sais-pas',   'icon' => 'question-mark'],
      ],
    ],
    [
      'id'         => 'hauteur',
      'question'   => 'Quelle est votre hauteur sous-plafond ?',
      'visibility' => ['sortie' => ['plafond']],
      'choices'    => [
        ['label' => 'Standard',    'dim' => '< 2,50 m',  'slug' => 'standard',    'icon' => 'ceiling-low'],
        ['label' => 'Confortable', 'dim' => '2,50–3 m',  'slug' => 'confortable', 'icon' => 'ceiling-mid'],
        ['label' => 'Haute',       'dim' => '> 3 m',     'slug' => 'haute',       'icon' => 'ceiling-high'],
      ],
    ],
    [
      'id'         => 'table',
      'question'   => 'Sera-t-il au-dessus d\'une table ou d\'un îlot ?',
      'visibility' => ['hauteur' => ['standard'], 'piece' => ['cuisine', 'bureau', 'salon', 'chambre']],
      'dynamic_question' => [
        'piece' => [
          'cuisine' => 'Sera-t-il au-dessus d\'une table ou d\'un îlot ?',
          'bureau'  => 'Sera-t-il au-dessus du bureau ?',
          'salon'   => 'Sera-t-il au-dessus d\'une table ?',
          'chambre' => 'Sera-t-il au-dessus du lit ?',
        ],
      ],
      'choices'    => [
        ['label' => 'Oui', 'slug' => 'oui', 'icon' => 'table-yes'],
        ['label' => 'Non', 'slug' => 'non', 'icon' => 'table-no'],
      ],
    ],
    [
      'id'         => 'style',
      'question'   => 'Quel est le style de votre intérieur ?',
      'visibility' => 'always',
      'choices'    => [
        ['label' => 'Moderne, neuf, tons clairs',        'slug' => 'moderne', 'icon' => 'minimal'],
        ['label' => 'Ancien, pierre, bois, tons chauds', 'slug' => 'ancien',  'icon' => 'organic'],
        ['label' => 'Pas de préférence',                 'slug' => 'neutre',  'icon' => 'question-mark'],
      ],
    ],
  ];
}

/**
 * Retourne la map des icônes SVG utilisées dans le questionnaire.
 */
function sapi_guide_get_icons() {
  return [
    // Sortie électrique
    'ceiling-plug' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18"/><path d="M12 3v4"/><circle cx="12" cy="10" r="3"/><path d="M10 13v2m4-2v2"/></svg>',
    'wall-plug'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3v18"/><rect x="8" y="8" width="8" height="8" rx="2"/><path d="M11 11v2m2-2v2"/></svg>',
    'no-plug'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="12" height="12" rx="2"/><path d="M10 10v4m4-4v4"/></svg>',
    'question-mark' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    'table-yes'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16"/><path d="M6 12v6m12-6v6"/><path d="M12 3v4"/><path d="M9 7h6l-1 5H10L9 7z"/></svg>',
    'table-no'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v6"/><path d="M9 9h6l-1 4h-4l-1-4z"/><path d="M3 21h18"/><path d="M7 21v-4m10 4v-4"/></svg>',
    // Hauteur plafond
    'ceiling-low'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18"/><path d="M12 3v6"/><path d="M9 9h6l-1 4h-4l-1-4z"/><path d="M3 21h18"/><path d="M7 21v-4m10 4v-4"/></svg>',
    'ceiling-mid'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2h18"/><path d="M12 2v8"/><path d="M9 10h6l-1 4h-4l-1-4z"/><path d="M3 22h18"/><path d="M7 22v-4m10 4v-4"/></svg>',
    'ceiling-high' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 1h18"/><path d="M12 1v10"/><path d="M9 11h6l-1 4h-4l-1-4z"/><path d="M3 23h18"/><path d="M7 23v-4m10 4v-4"/></svg>',
    // Taille pièce
    'square-sm'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="8" height="8" rx="1"/></svg>',
    'square-md'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="1"/></svg>',
    'square-lg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>',
    // Pièce
    'sofa'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><rect x="2" y="11" width="20" height="7" rx="2"/><path d="M5 18v2m14-2v2"/></svg>',
    'dining'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V20H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>',
    'bed'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>',
    'monitor'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>',
    'door'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="15" cy="12" r="1"/></svg>',
    'stairs'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21h4v-4h4v-4h4v-4h4V5"/></svg>',
    // Éclairage grande pièce
    'sun'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>',
    'lamp-desk'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21h6M12 21v-4"/><path d="M6 17l2-12h8l2 12"/><path d="M5 17h14"/></svg>',
    'cluster'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m-5-2v6m10-6v6"/><circle cx="12" cy="10" r="3"/><circle cx="7" cy="14" r="2.5"/><circle cx="17" cy="14" r="2.5"/></svg>',
    'clock'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    // Style
    'minimal'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><line x1="12" y1="8" x2="12" y2="16"/></svg>',
    'organic'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-3 0-6 2-7 5s0 7 2 9 6 3 9 2 5-4 5-7-1-6-3-8-3-1-6-1z"/><circle cx="12" cy="13" r="2.5"/></svg>',
  ];
}
