module.exports = ctx => ({
    map: false,
    parser: false,
    plugins: {
        'postcss-import': {
            root: ctx.file.dirname
        },
        'postcss-preset-env': {
            stage: 0
        },
        'postcss-csso': {
            grid: true
        }
    }
})