<template>
    <div>
        <Head title="Queue Jobs" />

        <Heading class="mb-6">Queue Jobs</Heading>

        <Card class="mb-6">
            <div class="p-4">
                <div v-if="loading">Loading jobs...</div>
                
                <div v-else>
                    <div v-for="(queueData, queueName) in jobs" :key="queueName" class="mb-6">
                        <h3 class="text-xl font-bold mb-2">{{ queueName }} Queue ({{ queueData.count }} jobs)</h3>
                        
                        <table class="w-full table-auto">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left">Job</th>
                                    <th class="px-4 py-2 text-left">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(job, index) in queueData.jobs" :key="index" class="border-t">
                                    <td class="px-4 py-2">{{ job.displayName || job.job || 'Unknown Job' }}</td>
                                    <td class="px-4 py-2">
                                        <pre class="whitespace-pre-wrap">{{ JSON.stringify(job.data, null, 2) }}</pre>
                                    </td>
                                </tr>
                                <tr v-if="queueData.jobs.length === 0">
                                    <td colspan="2" class="px-4 py-2 text-center">No jobs in queue</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <button 
                    @click="fetchJobs" 
                    class="bg-primary-500 hover:bg-primary-400 text-white font-bold py-2 px-4 rounded mt-4"
                >
                    Refresh Jobs
                </button>
            </div>
        </Card>
    </div>
</template>

<script>
export default {
    data() {
        return {
            jobs: {},
            loading: true,
        }
    },

    mounted() {
        this.fetchJobs()
    },

    methods: {
        async fetchJobs() {
            this.loading = true
            try {
                const response = await Nova.request().get('redis-jobs')
                this.jobs = response.data
            } catch (error) {
                console.error('Error fetching jobs:', error)
                Nova.error('Failed to load queue jobs')
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style>
.table {
    @apply min-w-full divide-y divide-gray-200;
}
.table th {
    @apply px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
}
.table td {
    @apply px-6 py-4 whitespace-nowrap text-sm text-gray-900;
}
pre {
    font-size: 0.875rem;
    max-height: 200px;
    overflow-y: auto;
}
</style> 