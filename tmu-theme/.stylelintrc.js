module.exports = {
    extends: ['stylelint-config-standard'],
    rules: {
        'at-rule-no-unknown': [
            true,
            {
                ignoreAtRules: [
                    'tailwind',
                    'apply',
                    'variants',
                    'responsive',
                    'screen',
                    'layer'
                ]
            }
        ],
        'declaration-block-trailing-semicolon': null,
        'no-descending-specificity': null,
        'selector-class-pattern': null,
        'function-no-unknown': [
            true,
            {
                ignoreFunctions: ['theme', 'screen']
            }
        ]
    },
    ignoreFiles: [
        'assets/build/**/*',
        'assets/dist/**/*',
        'node_modules/**/*',
        'vendor/**/*'
    ]
};