import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

export default defineConfig({
  legacy: {
    collections: true,
  },
  integrations: [
    starlight({
      title: 'ComercioLocal',
      description: 'Documentación técnica de la plataforma marketplace local',
      defaultLocale: 'root',
      locales: {
        root: {
          label: 'Español',
          lang: 'es',
        },
      },
      social: {
        github: 'https://github.com/JhonatanC4STRO/localstore-native',
      },
      sidebar: [
        {
          label: '00 · Vista General',
          autogenerate: { directory: '00-overview' },
        },
        {
          label: '01 · Producto',
          autogenerate: { directory: '01-product' },
        },
        {
          label: '02 · Arquitectura',
          autogenerate: { directory: '02-architecture' },
        },
        {
          label: '03 · Base de Datos',
          autogenerate: { directory: '03-database' },
        },
        {
          label: '04 · API',
          autogenerate: { directory: '04-api' },
        },
        {
          label: '05 · Frontend',
          autogenerate: { directory: '05-frontend' },
        },
        {
          label: '06 · Scraping & Datos',
          autogenerate: { directory: '06-scraping' },
        },
        {
          label: '07 · Seguridad',
          autogenerate: { directory: '07-security' },
        },
        {
          label: '08 · Despliegue',
          autogenerate: { directory: '08-deployment' },
        },
        {
          label: '09 · Desarrollo',
          autogenerate: { directory: '09-development' },
        },
        {
          label: '10 · Legal',
          autogenerate: { directory: '10-legal' },
        },
      ],
      customCss: ['./src/styles/custom.css'],
    }),
  ],
});
