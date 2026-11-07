import './bootstrap';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Check,
    ClipboardList,
    CircleHelp,
    Eye,
    EyeOff,
    KeyRound,
    LayoutDashboard,
    LockKeyhole,
    LogOut,
    Mail,
    Menu,
    MessageSquare,
    ShieldCheck,
    Users,
    X,
    createIcons,
} from 'lucide';

createIcons({
    icons: {
        ArrowRight,
        BarChart3,
        BookOpen,
        Check,
        ClipboardList,
        CircleHelp,
        Eye,
        EyeOff,
        KeyRound,
        LayoutDashboard,
        LockKeyhole,
        LogOut,
        Mail,
        Menu,
        MessageSquare,
        ShieldCheck,
        Users,
        X,
    },
});

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';
        button.innerHTML = `<i data-lucide="${isPassword ? 'eye-off' : 'eye'}" class="h-4 w-4"></i>`;
        createIcons({ icons: { Eye, EyeOff } });
    });
});

const sidebar = document.getElementById('admin-sidebar');
const sidebarBackdrop = document.getElementById('sidebar-backdrop');

function closeSidebar() {
    sidebar?.classList.add('-translate-x-full');
    sidebarBackdrop?.classList.add('hidden');
}

document.getElementById('sidebar-open')?.addEventListener('click', () => {
    sidebar?.classList.remove('-translate-x-full');
    sidebarBackdrop?.classList.remove('hidden');
});

document.getElementById('sidebar-close')?.addEventListener('click', closeSidebar);
sidebarBackdrop?.addEventListener('click', closeSidebar);

if (document.querySelector('[data-material-editor]')) {
    import('./material-editor').then(({ initializeMaterialEditors }) => initializeMaterialEditors());
}

if (document.querySelector('[data-question-bank]') || document.querySelector('[data-question-import-page]')) {
    import('./question-bank').then(({ initializeQuestionBank, initializeQuestionImportPage }) => {
        initializeQuestionBank();
        initializeQuestionImportPage();
    });
}
