import { Home, User, Briefcase, FileText } from 'lucide-react'
import { NavBar } from "@/components/ui/tubelight-navbar"

export function NavBarDemo() {
  const navItems = [
    { name: 'ホーム', url: '/home', icon: Home },
    { name: 'マイページ', url: '/dashboard', icon: User },
    { name: 'コミュニティ', url: '/community', icon: Briefcase },
    { name: '設定', url: '/settings/profile', icon: FileText }
  ]

  return <NavBar items={navItems} />
} 