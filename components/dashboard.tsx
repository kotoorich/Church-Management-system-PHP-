import { Card, CardContent, CardHeader, CardTitle } from './ui/card'
import { Users, DollarSign, Calendar, TrendingUp } from 'lucide-react'

interface DashboardProps {
  members: any[]
  payments: any[]
}

export function Dashboard({ members, payments }: DashboardProps) {
  const totalMembers = members.length
  const totalPayments = payments.reduce((sum, payment) => sum + payment.amount, 0)
  const thisMonthPayments = payments.filter(payment => {
    const paymentDate = new Date(payment.date)
    const now = new Date()
    return paymentDate.getMonth() === now.getMonth() && paymentDate.getFullYear() === now.getFullYear()
  }).length

  const stats = [
    {
      title: 'Total Members',
      value: totalMembers,
      icon: Users,
      description: 'Active church members',
    },
    {
      title: 'Total Donations',
      value: `$${totalPayments.toLocaleString()}`,
      icon: DollarSign,
      description: 'All time donations',
    },
    {
      title: 'This Month',
      value: thisMonthPayments,
      icon: Calendar,
      description: 'Payments this month',
    },
    {
      title: 'Average Donation',
      value: payments.length > 0 ? `$${(totalPayments / payments.length).toFixed(0)}` : '$0',
      icon: TrendingUp,
      description: 'Per transaction',
    },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-3xl mb-2">Church Dashboard</h2>
        <p className="text-gray-600">Overview of church activities and finances</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat, index) => {
          const Icon = stat.icon
          return (
            <Card key={index}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stat.value}</div>
                <p className="text-xs text-muted-foreground">{stat.description}</p>
              </CardContent>
            </Card>
          )
        })}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Recent Members</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {members.slice(-5).map((member) => (
                <div key={member.id} className="flex items-center space-x-3">
                  <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                    {member.name.split(' ').map((n: string) => n[0]).join('')}
                  </div>
                  <div>
                    <p className="font-medium">{member.name}</p>
                    <p className="text-sm text-gray-600">{member.email}</p>
                  </div>
                </div>
              ))}
              {members.length === 0 && (
                <p className="text-gray-500">No members added yet</p>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Recent Payments</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {payments.slice(-5).map((payment) => {
                const member = members.find(m => m.id === payment.memberId)
                return (
                  <div key={payment.id} className="flex items-center justify-between">
                    <div>
                      <p className="font-medium">{member?.name || 'Unknown Member'}</p>
                      <p className="text-sm text-gray-600">{payment.type}</p>
                    </div>
                    <div className="text-right">
                      <p className="font-medium">${payment.amount}</p>
                      <p className="text-sm text-gray-600">{new Date(payment.date).toLocaleDateString()}</p>
                    </div>
                  </div>
                )
              })}
              {payments.length === 0 && (
                <p className="text-gray-500">No payments recorded yet</p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}