<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">General Tools</h1>
    <button 
      @click="updateAssessments" 
      :class="{'opacity-50 cursor-not-allowed': loading}" 
      :disabled="loading"
      class="btn btn-default bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
    >
      <span v-if="loading">Updating...</span>
      <span v-else>Update Assessments</span>
    </button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      loading: false,
    };
  },
  methods: {
    updateAssessments() {
      this.loading = true;
      Nova.request().post('GeneralTools/update-assessments')
        .then(response => {
          Nova.success(response.data.message);
        })
        .catch(error => {
          Nova.error('Failed to update assessments.');
        })
        .finally(() => {
          this.loading = false;
        });
    }
  }
}
</script> 