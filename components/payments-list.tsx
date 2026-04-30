import { useState } from 'react'
import { Button } from './ui/button'
import { Card, CardContent, CardHeader, CardTitle } from './ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './ui/table'
import { Badge } from './ui/badge'
import { Input } from './ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select'
import { Plus, Edit, Trash2, Search } from 'lucide-react'

interface Payment {
  id: string
  memberId: string
  amount: number
  type: string
  date: string
  description: string
}

interface PaymentsListProps {
  payments: Payment[]
  members: any[]
  onAddPayment: () => void
  onEditPayment: (payment: Payment) => void
  onDeletePayment: (paymentId: string) => void
}

export function PaymentsList({ 
  payments, 
  members, 
  onAddPayment, 
  onEditPayment, 
  onDeletePayment 
}: PaymentsListProps) {
  const [searchTerm, setSearchTerm] = useState('')
  const [filterType, setFilterType] = useState('all')
  const [filterMonth, setFilterMonth] = useState('all')

  const getMemberName = (memberId: string) => {
    const member = members.find(m => m.id === memberId)
    return member ? member.name : 'Unknown Member'
  }

  const filteredPayments = payments.filter(payment => {
    const memberName = getMemberName(payment.memberId).toLowerCase()
    const matchesSearch = memberName.includes(searchTerm.toLowerCase()) || 
                         payment.type.toLowerCase().includes(searchTerm.toLowerCase())
    
    const matchesType = filterType === 'all' || payment.type === filterType
    
    const paymentDate = new Date(payment.date)
    const currentYear = new Date().getFullYear()
    const matchesMonth = filterMonth === 'all' || 
                        (paymentDate.getMonth() === parseInt(filterMonth) && 
                         paymentDate.getFullYear() === currentYear)
    
    return matchesSearch && matchesType && matchesMonth
  })

  const totalAmount = filteredPayments.reduce((sum, payment) => sum + payment.amount, 0)

  const paymentTypes = [...new Set(payments.map(p => p.type))]
  
  const months = [
    { value: '0', label: 'January' },
    { value: '1', label: 'February' },
    { value: '2', label: 'March' },
    { value: '3', label: 'April' },
    { value: '4', label: 'May' },
    { value: '5', label: 'June' },
    { value: '6', label: 'July' },
    { value: '7', label: 'August' },
    { value: '8', label: 'September' },
    { value: '9', label: 'October' },
    { value: '10', label: 'November' },
    { value: '11', label: 'December' },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl mb-2">Payment Records</h2>
          <p className="text-gray-600">Track all church donations and payments</p>
        </div>
        <Button onClick={onAddPayment}>
          <Plus className="mr-2 h-4 w-4" />
          Add Payment
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            <CardTitle>
              All Payments ({filteredPayments.length} records) - Total: ${totalAmount.toLocaleString()}
            </CardTitle>
            
            <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search payments..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 w-full sm:w-64"
                />
              </div>
              
              <Select value={filterType} onValueChange={setFilterType}>
                <SelectTrigger className="w-full sm:w-40">
                  <SelectValue placeholder="Filter by type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Types</SelectItem>
                  {paymentTypes.map(type => (
                    <SelectItem key={type} value={type}>{type}</SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select value={filterMonth} onValueChange={setFilterMonth}>
                <SelectTrigger className="w-full sm:w-40">
                  <SelectValue placeholder="Filter by month" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Months</SelectItem>
                  {months.map(month => (
                    <SelectItem key={month.value} value={month.value}>{month.label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Member</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Description</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredPayments
                .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
                .map((payment) => (
                <TableRow key={payment.id}>
                  <TableCell>{new Date(payment.date).toLocaleDateString()}</TableCell>
                  <TableCell className="font-medium">{getMemberName(payment.memberId)}</TableCell>
                  <TableCell>
                    <Badge variant="outline">{payment.type}</Badge>
                  </TableCell>
                  <TableCell>${payment.amount.toLocaleString()}</TableCell>
                  <TableCell className="max-w-xs truncate">{payment.description || '-'}</TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end space-x-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onEditPayment(payment)}
                      >
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onDeletePayment(payment.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
              {filteredPayments.length === 0 && (
                <TableRow>
                  <TableCell colSpan={6} className="text-center py-8">
                    <p className="text-gray-500">
                      {payments.length === 0 
                        ? 'No payments recorded yet. Add your first payment to get started.'
                        : 'No payments match your current filters.'}
                    </p>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  )
}