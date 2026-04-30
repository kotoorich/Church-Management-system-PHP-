import { ReactNode } from 'react'
import { Button } from './ui/button'
import { Card, CardContent } from './ui/card'
import { Users, DollarSign, Home, Settings } from 'lucide-react'

interface LayoutProps {
  children: ReactNode
  currentPage: string
  onPageChange: (page: string) => void
}

export function Layout({ children, currentPage, onPageChange }: LayoutProps) {
  const navigation = [
    { id: 'dashboard', label: 'Dashboard', icon: Home },
    { id: 'members', label: 'Members', icon: Users },
    { id: 'payments', label: 'Payments', icon: DollarSign },
    { id: 'settings', label: 'Settings', icon: Settings },
  ]

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="px-6 py-4">
          <h1 className="text-2xl text-gray-900">Grace Community Church</h1>
          <p className="text-gray-600">Church Management System</p>
        </div>
      </header>

      <div className="flex">
        {/* Sidebar */}
        <aside className="w-64 bg-white shadow-sm h-[calc(100vh-80px)]">
          <nav className="p-4 space-y-2">
            {navigation.map((item) => {
              const Icon = item.icon
              return (
                <Button
                  key={item.id}
                  variant={currentPage === item.id ? 'default' : 'ghost'}
                  className="w-full justify-start"
                  onClick={() => onPageChange(item.id)}
                >
                  <Icon className="mr-2 h-4 w-4" />
                  {item.label}
                </Button>
              )
            })}
          </nav>
        </aside>

        {/* Main Content */}
        <main className="flex-1 p-6">
          {children}
        </main>
      </div>
    </div>
  )
}