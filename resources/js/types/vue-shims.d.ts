// Declarações de módulo TypeScript para ficheiros .vue. Permite importar componentes Vue no TypeScript.
// TypeScript module declarations for .vue files. Allows importing Vue components in TypeScript.
declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent;
    export default component;
}
