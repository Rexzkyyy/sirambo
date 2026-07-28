import './bootstrap';
import { 
    createIcons, Menu, X, PanelLeft, ChevronDown, ChevronRight, LayoutDashboard,
    Database, DatabaseZap, FileText, ClipboardList, Table2, Download, Upload, Save,
    Users, LogOut, Lock, Unlock,
    BarChart2, BarChart3, Percent,
    RefreshCw, Search, Filter, Eye, EyeOff,
    AlertTriangle, CheckCircle, Info, XCircle, HelpCircle,
    ArrowLeft, ArrowRight,
    Building2, MapPin, Factory, ShoppingBag, Settings2,
    Crown, Medal, Trophy,
    // Dynamic icons for lembar kerja
    Leaf, TreePine, Fish, PawPrint, Hammer, Flame, Zap, Droplets,
    Truck, Bed, Utensils, Wifi, Radio, Banknote, Shield, Home, Briefcase,
    GraduationCap, HeartPulse, Landmark, Ship, ShoppingCart, LineChart, Boxes, Receipt,
    Circle, Square, Tag, Bookmark, Hash, ListCheck, CheckSquare,
    Layers, Grid2x2, LayoutGrid, Folder, Box, PieChart, Activity, Package
} from 'lucide';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Semua icon yang digunakan di seluruh aplikasi
const appIcons = {
    Menu, X, PanelLeft, ChevronDown, ChevronRight, LayoutDashboard,
    Database, DatabaseZap, FileText, ClipboardList, Table2, Download, Upload, Save,
    Users, LogOut, Lock, Unlock,
    BarChart2, BarChart3, Percent,
    RefreshCw, Search, Filter, Eye, EyeOff,
    AlertTriangle, CheckCircle, Info, XCircle, HelpCircle,
    ArrowLeft, ArrowRight,
    Building2, MapPin, Factory, ShoppingBag, Settings2,
    Crown, Medal, Trophy,
    // Dynamic icons
    Leaf, TreePine, Fish, PawPrint, Hammer, Flame, Zap, Droplets,
    Truck, Bed, Utensils, Wifi, Radio, Banknote, Shield, Home, Briefcase,
    GraduationCap, HeartPulse, Landmark, Ship, ShoppingCart, LineChart, Boxes, Receipt,
    Circle, Square, Tag, Bookmark, Hash, ListCheck, CheckSquare,
    Layers, Grid2x2, LayoutGrid, Folder, Box, PieChart, Activity, Package
};

// Wrap createIcons on the window object to provide the icons object by default
window.lucide = { 
    createIcons: (options = {}) => {
        // If options is a string or doesn't have icons, merge with appIcons
        const finalOptions = {
            icons: appIcons,
            ...(options && typeof options === 'object' ? options : {})
        };
        return createIcons(finalOptions);
    }, 
    icons: appIcons 
};

document.addEventListener('DOMContentLoaded', () => {
    window.lucide.createIcons();
});
