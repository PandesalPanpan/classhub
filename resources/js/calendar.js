import { Calendar } from '@fullcalendar/core';
import resourcePlugin from '@fullcalendar/resource';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';

const palette = [
    '#2563eb', // blue-600
    '#7c3aed', // violet-600
    '#0891b2', // cyan-600
    '#16a34a', // green-600
    '#d97706', // amber-600
    '#dc2626', // red-600
    '#0ea5e9', // sky-500
    '#9333ea', // purple-600
];

function hashTitleToColor(title) {
    let hash = 0;
    for (let i = 0; i < title.length; i++) {
        hash = ((hash << 5) - hash + title.charCodeAt(i)) | 0; // djb2-ish
    }
    const idx = Math.abs(hash) % palette.length;
    return palette[idx];
}

function withHashedColors(evts) {
    return (evts || []).map((evt) => {
        const color = hashTitleToColor(evt.title || '');
        return { ...evt, backgroundColor: color, borderColor: color };
    });
}

// Expose for Livewire updates
window.withHashedColors = withHashedColors;

window.initClassroomCalendar = function (rooms, events) {
    const calendarEl = document.getElementById('classroom-calendar');

    if (!calendarEl || calendarEl.dataset.initialized) return;

    calendarEl.dataset.initialized = 'true';

    const calendar = new Calendar(calendarEl, {
        schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
        plugins: [resourcePlugin, resourceTimelinePlugin, interactionPlugin],
        initialView: 'resourceTimelineDay',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'resourceTimelineDay'
        },
        resources: rooms,
        events: withHashedColors(events),
        resourceAreaWidth: '10%',
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        height: 'auto',
        aspectRatio: 1.8,
        editable: false,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        nowIndicator: true,
        // resourceLabelContent: function(arg) {
        //     return arg.resource.title;
        // },
        // eventContent: function(arg) {
        //     return {
        //         html: '<div class="fc-event-title">' + arg.event.title + '</div>'
        //     };
        // },
    });

    calendar.render();

    // Apply custom styles after calendar renders with a slight delay
    // setTimeout(() => {
    //    applyCalendarStyles();
    // }, 100);

    // Re-apply styles when calendar updates
    // calendar.on('eventsSet', () => setTimeout(applyCalendarStyles, 50));
    // calendar.on('resourcesSet', () => setTimeout(applyCalendarStyles, 50));
    // calendar.on('viewDidMount', () => setTimeout(applyCalendarStyles, 50));

    return calendar;
};

function applyCalendarStyles() {
    const calendarEl = document.getElementById('classroom-calendar');
    if (!calendarEl) return;

    // Check if dark mode is active
    const isDark = document.documentElement.classList.contains('dark') ||
        document.body.classList.contains('dark') ||
        window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (isDark) {
        // Set CSS variables on the calendar element (FullCalendar way)
        const fcElement = calendarEl.querySelector('.fc') || calendarEl;
        fcElement.style.setProperty('--fc-neutral-text-color', '#ffffff');
        fcElement.style.setProperty('--fc-neutral-bg-color', '#1a1a1a');
        fcElement.style.setProperty('--fc-border-color', 'rgba(255, 255, 255, 0.2)');

        // Style resource cells (room labels) - using FullCalendar classes
        // const resourceCells = calendarEl.querySelectorAll('.fc-resource-cell, .fc-datagrid-cell');
        // resourceCells.forEach(cell => {
        //     cell.style.cssText += 'background-color: #1a1a1a !important; color: #ffffff !important;';

        //     // Target all possible text containers
        //     const allElements = cell.querySelectorAll('*');
        //     allElements.forEach(el => {
        //         el.style.cssText += 'color: #ffffff !important;';
        //         if (el.tagName === 'SPAN' || el.tagName === 'DIV' || el.tagName === 'TD') {
        //             el.style.cssText += 'font-weight: 600 !important;';
        //         }
        //     });
        // });

        // Style resource cell text specifically (FullCalendar uses .fc-resource-cell-text or similar)
        // const resourceTexts = calendarEl.querySelectorAll(
        //     '.fc-resource-cell-text, .fc-datagrid-cell-cushion, .fc-resource-cell-main, .fc-resource-cell-content, .fc-resource-cell-frame'
        // );
        // resourceTexts.forEach(text => {
        //     text.style.cssText += 'color: #ffffff !important; font-weight: 600 !important;';
        // });

        // Style resource area
        // const resourceArea = calendarEl.querySelector('.fc-resource-area, .fc-datagrid');
        // if (resourceArea) {
        //     resourceArea.style.cssText += 'background-color: #1a1a1a !important;';
        // }

        // Style column headers (time slots like 8am, 9am)
        const colHeaders = calendarEl.querySelectorAll('.fc-col-header-cell, .fc-timeline-header-cell');
        colHeaders.forEach(header => {
            header.style.cssText += 'background-color: #1a1a1a !important; border-color: rgba(255, 255, 255, 0.2) !important;';
        });

        // Style column header text (8am, 9am, etc.) - CRITICAL for readability
        const colHeaderTexts = calendarEl.querySelectorAll(
            '.fc-col-header-cell-cushion, .fc-timeline-header-cell-cushion, .fc-timegrid-slot-label'
        );
        colHeaderTexts.forEach(text => {
            text.style.cssText += 'color: #ffffff !important; font-weight: 600 !important;';
        });

        // Style timeline slot labels (the time labels in the timeline)
        // const timelineSlots = calendarEl.querySelectorAll(
        //     '.fc-timeline-slot-cushion, .fc-timegrid-slot-label-cushion, .fc-timeline-slot-label'
        // );
        // timelineSlots.forEach(slot => {
        //     slot.style.cssText += 'color: #ffffff !important; font-weight: 500 !important;';
        // });

        // Style toolbar
        const toolbar = calendarEl.querySelector('.fc-toolbar');
        if (toolbar) {
            toolbar.style.cssText += 'color: #ffffff !important;';
        }

        const toolbarTitle = calendarEl.querySelector('.fc-toolbar-title');
        if (toolbarTitle) {
            toolbarTitle.style.cssText += 'color: #ffffff !important; font-weight: 600 !important;';
        }

        // Style buttons
        const buttons = calendarEl.querySelectorAll('.fc-button');
        buttons.forEach(button => {
            button.style.cssText += 'background-color: #2a2a2a !important; border-color: rgba(255, 255, 255, 0.2) !important; color: #ffffff !important;';
        });

        const activeButtons = calendarEl.querySelectorAll('.fc-button-active');
        activeButtons.forEach(button => {
            button.style.cssText += 'background-color: #3b82f6 !important; border-color: #3b82f6 !important; color: #ffffff !important;';
        });
    }
}

