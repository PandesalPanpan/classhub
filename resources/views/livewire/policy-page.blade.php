<div class="container mx-auto max-w-4xl px-4 py-8 text-zinc-900 dark:text-zinc-50">
    <h1 class="mb-6 text-3xl font-bold">CPE Room Utilization Terms &amp; Conditions</h1>

    <div class="mb-6 text-sm text-gray-600 dark:text-gray-400">
        <p><strong>Last Updated:</strong> {{ $policyUpdatedAt ? $policyUpdatedAt->format('F j, Y') : 'N/A' }}</p>
        <p><strong>Administering Office:</strong> Computer Engineering (CPE) Laboratory Office</p>
    </div>

    @if (filled($policyHtml))
        <article class="max-w-none text-base leading-7 text-zinc-800 dark:text-zinc-100 [&_h1]:mt-8 [&_h1]:mb-4 [&_h1]:text-3xl [&_h1]:font-bold [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:text-2xl [&_h2]:font-semibold [&_h3]:mt-6 [&_h3]:mb-2 [&_h3]:text-xl [&_h3]:font-semibold [&_p]:my-4 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:my-1 [&_strong]:font-semibold [&_a]:text-red-700 [&_a]:underline dark:[&_a]:text-red-300">
            {!! $policyHtml !!}
        </article>
    @else
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-gray-600 dark:border-gray-600 dark:text-gray-300">
            No policy has been published yet.
        </div>
    @endif
</div>
