import { terser } from "rollup-plugin-terser";
import resolve from 'rollup-plugin-node-resolve';
import commonjs from 'rollup-plugin-commonjs';

export default {
    input: 'Assets/app.js',
    output: {
        file: 'public/js/app.js',
        format: 'iife',
    },
    plugins: [
        terser(),
        resolve(),
        commonjs()
    ],
    // external: [ 'balajs' ]
}