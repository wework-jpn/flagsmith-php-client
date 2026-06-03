module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [2, 'always', [
            'feat', 'fix', 'docs', 'style', 'refactor', 'test', 'build', 'ci', 'chore', 'perf', 'revert',
        ]],
        'subject-case': [
            2,
            'never',
            ['sentence-case', 'start-case', 'pascal-case', 'upper-case'],
        ],
        'header-max-length': [2, 'always', 512],
        'header-format': [2, 'always', /^(feat|fix|docs|style|refactor|test|build|ci|chore|perf|revert)\([^)]+\): .+( \[JDTD-\d+\])?$/],
        'body-leading-blank': [2, 'always'],
        'body-max-line-length': [2, 'always', 256],
        'body-empty': [2, 'never'],
        'footer-leading-blank': [2, 'always'],
        'footer-max-line-length': [2, 'always', 100],
        'subject-empty': [2, 'never'],
        'subject-full-stop': [2, 'never', '.'],
        'type-case': [2, 'always', 'lower-case'],
        'type-empty': [2, 'never'],
    },
    plugins: [
        {
            rules: {
                'header-format': ({ header }) => {
                    const headerRegex = /^(feat|fix|docs|style|refactor|test|build|ci|chore|perf|revert)\([^)]+\): .+( \[JDTD-\d+\])?$/;
                    return [
                        headerRegex.test(header),
                        'header must match the pattern: <type>(<scope>): <subject> with optional [JDTD-xxxx]',
                    ];
                },
            },
        },
    ],
};
