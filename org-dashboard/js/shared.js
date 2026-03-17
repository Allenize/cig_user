const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const closeSidebar = document.getElementById('closeSidebar');
const overlay = document.getElementById('overlay');

function openSidebar() {
  sidebar.classList.add('open');
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeSidebarFunc() {
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
  document.body.style.overflow = '';
}

if (menuToggle) {
  menuToggle.addEventListener('click', openSidebar);
}
if (closeSidebar) {
  closeSidebar.addEventListener('click', closeSidebarFunc);
}
if (overlay) {
  overlay.addEventListener('click', closeSidebarFunc);
}

window.addEventListener('resize', () => {
  if (window.innerWidth > 900) {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  }
});