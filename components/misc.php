<script>
  document.addEventListener('DOMContentLoaded', function () {
    const openIconBtn = document.querySelector('.open-icon-btn');
    $(openIconBtn).toggleClass('d-none');
    $(openIconBtn).toggleClass('show');
  });
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

function closeChatPannel() {
  const chatPannel = document.querySelector('.chat-pannel');
  chatPannel.classList.add('d-none');

  console.log($('.open-icon-btn'));
  $('.open-icon-btn').addClass('show');
  $('.open-icon-btn').removeClass('d-none');
  console.log($('.open-icon-btn'));
}
function openChatPannel() {
  const openIconBtn = document.querySelector('.open-icon-btn');
  $(openIconBtn).toggleClass('d-none');
  $(openIconBtn).toggleClass('show');
  const chatPannel = document.querySelector('.chat-pannel');
  chatPannel.classList.remove('d-none');  
}
function changeTheme(theme) {
  // List of all possible theme classes
  const themes = ['light-mode', 'dark-mode', 'brown-mode', 'purple-mode', 'black-mode'];
  // Remove all theme classes from the body
  themes.forEach(function(currentTheme) {
    $('body').removeClass(currentTheme);
  });
  // Add the selected theme
  $('body').addClass(theme);
  // console.log(theme);
}
function toggleThemeClick() {
const themeContainer = document.querySelector('.theme-icon-container');
themeContainer.classList.toggle('d-none');
}

</script>