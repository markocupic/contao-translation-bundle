const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/')
    .setPublicPath('/bundles/markocupiccontaotranslation')
    .setManifestKeyPrefix('')

    //.addEntry('backend', './assets/backend.js') // Register Stimulus controllers

    .copyFiles({
        from: './node_modules/bootstrap/dist/css',
        to: 'bootstrap/dist/css/[path][name].[hash:8].[ext]',
        pattern: /(bootstrap\.min\.css)$/,
    })
    .copyFiles({
        from: './node_modules/bootstrap/dist/js',
        to: 'bootstrap/dist/js/[path][name].[hash:8].[ext]',
        pattern: /(bootstrap\.min\.js)$/,
    })
    .copyFiles({
        from: './node_modules/swapy/dist',
        to: 'swapy/dist/[path][name].[hash:8].[ext]',
        pattern: /(swapy\.min\.js)$/,
    })
    .copyFiles({
        from: './assets/icons',
        to: 'icons/[path][name].[ext]',
    })
    .copyFiles({
        from: './node_modules/vue/dist',
        to: 'vue/dist/[path][name].[hash:8].[ext]',
        pattern: /(vue\.global\.prod\.js)$/,
    })
    .copyFiles({
        from: './assets/js',
        to: 'js/[path][name].[hash:8].[ext]',
    })

    // Typescripts
    //.addEntry('js/avatar_uploader', './assets/ts/avatar_uploader.ts')
    //.enableTypeScriptLoader()

    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps()
    .enableVersioning()

    .enablePostCssLoader()
    // Preprocessing scss to css
    .enableSassLoader()
    .enablePostCssLoader()
    .addStyleEntry('css/styles', './assets/css/styles.scss')

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    .enablePostCssLoader()
;

module.exports = Encore.getWebpackConfig();
