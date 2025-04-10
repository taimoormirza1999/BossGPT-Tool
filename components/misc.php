<script>
const notificationIcons = {
    project_created: `<?= getFolderIcon(); ?>`,
  user_removed: `<?= getProfileDeleteIcon(); ?>`,
  user_assigned: `<?= getProfileIcon(); ?>`,
  task_created: `<?= getclipboardIcon(); ?>`,
  task_status_updated: `<?= getclipboardIcon(); ?>`,
  task_updated: `<?= getclipboardIcon(); ?>`,
  subtask_status_updated: `<?= getPaperclipIcon(); ?>`,
  subtask_created: `<?= getPaperclipIcon(); ?>`,
  subtask_deleted: `<?= getPaperclipIcon(); ?>`,
  subtask_updated: `<?= getPaperclipIcon(); ?>`,
  user_assigned: `<?= getProfileIcon(); ?>`,
};

function getNotificationIcon(action_type) {
  return notificationIcons[action_type] || '';
}

function getNotificationIconClass(action_type) {
  return "notification-icon-" + action_type|| '';
}
document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(el => {
    new bootstrap.Tooltip(el);
  });
});

</script>