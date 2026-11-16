// The sections of each policy page (PLAN M23), in reading order: the translation key prefix of
// each section in lang/{bn,en}/policies.php (its `.title` and `.body`).
export const policySections = {
    code_of_conduct: [
        'policies.code_of_conduct.sections.work',
        'policies.code_of_conduct.sections.people',
        'policies.code_of_conduct.sections.good_faith',
        'policies.code_of_conduct.sections.honesty',
        'policies.code_of_conduct.sections.privacy',
        'policies.code_of_conduct.sections.enforcement',
    ],
    editorial: [
        'policies.editorial.sections.status',
        'policies.editorial.sections.ai',
        'policies.editorial.sections.numbers',
        'policies.editorial.sections.tone',
        'policies.editorial.sections.future',
        'policies.editorial.sections.corrections',
    ],
    privacy: [
        'policies.privacy.sections.reading',
        'policies.privacy.sections.account',
        'policies.privacy.sections.public',
        'policies.privacy.sections.guide',
        'policies.privacy.sections.private',
        'policies.privacy.sections.cookies',
        'policies.privacy.sections.errors',
        'policies.privacy.sections.providers',
        'policies.privacy.sections.rights',
    ],
    not_advice: [
        'policies.not_advice.sections.general',
        'policies.not_advice.sections.health',
        'policies.not_advice.sections.legal_money',
        'policies.not_advice.sections.emergency',
    ],
    neutrality: [
        'policies.neutrality.sections.nonprofit',
        'policies.neutrality.sections.neutral',
        'policies.neutrality.sections.sponsored',
        'policies.neutrality.sections.accounts',
        'policies.neutrality.sections.conflicts',
    ],
} as const;

export type PolicyKey = keyof typeof policySections;

export type PolicySectionKey = (typeof policySections)[PolicyKey][number];

export type PolicyLink = {
    slug: string;
    key: PolicyKey;
};
