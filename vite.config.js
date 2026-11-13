import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/timetable-matrix.js',
                'resources/js/calendar.js',
                'resources/js/schedules.js',
                'resources/js/lanes.js',
                'resources/js/staff-timeline.js',
                'resources/js/timetable-assignments.js',
                'resources/js/timetable-task-modal.js',
                'resources/js/presentation.js',
                'resources/js/locations.js',
                'resources/js/equipments.js',
                'resources/js/equipment-patterns.js',
                'resources/js/logistics.js',
                'resources/js/staff-myshift.js',
                'resources/js/organizations.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
