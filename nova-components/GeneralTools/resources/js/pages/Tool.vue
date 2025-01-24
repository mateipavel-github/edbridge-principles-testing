<template>
  <div>
      <h1>General Tools</h1>
      <button @click="updateAssessments" class="btn btn-default">Update Assessments</button>
  </div>
</template>

<script>
export default {
  methods: {
      updateAssessments() {
          Nova.request().post('GeneralTools/update-assessments')
              .then(response => {
                  this.$toasted.show(response.data.message, { type: 'success' });
              })
              .catch(error => {
                  this.$toasted.show('Failed to update assessments.', { type: 'error' });
              });
      }
  }
}
</script> 