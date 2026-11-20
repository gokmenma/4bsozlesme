import {
  Banknote,
  Building2,
  Calculator,
  FileCog,
  FileText,
  ListTodo,
  MessageSquare,
  Settings,
  ShieldCheck,
  UsersRound,
  Coins,
  CreditCard,
  Users,
} from '@lucide/vue'

export const MODULE_GROUPS = [
  { id: 'yonetim', label: 'YÖNETİM' },
  { id: 'diger', label: 'DİĞER İŞLEMLER' },
  { id: 'sistem', label: 'SİSTEM YÖNETİMİ' },
]

export const MODULES = [
  { key: 'personnel', route: '/personel-listesi', to: '/personel', group: 'yonetim', label: 'Personel Yönetimi', navLabel: 'Personel', subtitle: 'Personel listesi, özlük ve durum takibi', icon: UsersRound, theme: 'blue' },
  { key: 'wages', route: '/ucret-tanimlari', to: '/ucret-tanimlari', group: 'yonetim', label: 'Ücret Tanımları', navLabel: 'Ücretler', subtitle: 'Unvan ve ücret tanımları', icon: Coins, theme: 'amber' },
  { key: 'matrah', route: '/matrah-yonetimi', to: '/matrah', group: 'yonetim', label: 'Matrah Yönetimi', navLabel: 'Matrah', subtitle: 'Teşvik ek ödeme matrahları', icon: Calculator, theme: 'emerald' },
  { key: 'templates', route: '/sozlesme-taslagi', to: '/sozlesme-taslagi', group: 'diger', label: 'Sözleşme Taslağı', navLabel: 'Sözleşme', subtitle: 'Sözleşme şablonları ve çıktıları', icon: FileText, theme: 'purple' },
  { key: 'definitions', route: '/tanimlamalar', to: '/tanimlamalar', group: 'diger', label: 'Tanımlamalar', navLabel: 'Tanımlar', subtitle: 'Sistem tanım ve parametreleri', icon: FileCog, theme: 'blue' },
  { key: 'tasks', route: '/yapilacaklar', to: '/yapilacaklar', group: 'diger', label: 'Yapılacaklar', navLabel: 'Görevler', subtitle: 'Kanban görev panosu', icon: ListTodo, theme: 'emerald' },
  { key: 'doner', route: '/doner-matrahi-olustur', to: '/doner-matrahi', group: 'diger', label: 'Döner Matrahı', navLabel: 'Döner', subtitle: 'Döner matrahı oluştur ve indir', icon: Banknote, theme: 'amber' },
  { key: 'users', route: '/kullanicilar', to: '/kullanicilar', group: 'sistem', label: 'Kullanıcılar', navLabel: 'Kullanıcı', subtitle: 'Kullanıcı hesapları ve roller', icon: Users, theme: 'blue' },
  { key: 'roles', route: '/yetki-gruplari', to: '/yetki-gruplari', group: 'sistem', label: 'Yetki Grupları', navLabel: 'Yetkiler', subtitle: 'Rol ve izin yönetimi', icon: ShieldCheck, theme: 'purple' },
  { key: 'tenants', route: '/kurum-yonetimi', to: '/kurum-yonetimi', group: 'sistem', label: 'Kurum Yönetimi', navLabel: 'Kurumlar', subtitle: 'Kurum kayıtları', icon: Building2, theme: 'coral', superadminOnly: true },
  { key: 'subscription', route: '/abonelik', to: '/abonelik', group: 'sistem', label: 'Abonelik', navLabel: 'Abonelik', subtitle: 'Abonelik ve deneme süreleri', icon: CreditCard, theme: 'amber', superadminOnly: true },
  { key: 'settings', route: '/ayarlar', to: '/ayarlar', group: 'sistem', label: 'Sistem Ayarları', navLabel: 'Ayarlar', subtitle: 'Genel sistem ayarları', icon: Settings, theme: 'coral', superadminOnly: true },
  { key: 'feedbacks', route: '/geri-bildirimler', to: '/geri-bildirimler', group: 'sistem', label: 'Geri Bildirimler', navLabel: 'Geri Bildirim', subtitle: 'Kullanıcı geri bildirimleri', icon: MessageSquare, theme: 'purple', superadminOnly: true },
]

export const DOCK_KEYS = ['personnel', 'tasks', 'wages']

export function moduleByPath(path) {
  return MODULES.find((m) => m.to === path) || null
}
