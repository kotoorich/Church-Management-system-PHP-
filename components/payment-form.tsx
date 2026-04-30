import { useState, useEffect } from 'react'
import { Button } from './ui/button'
import { Card, CardContent, CardHeader, CardTitle } from './ui/card'
import { Input } from './ui/input'
import { Label } from './ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select'
import { Textarea } from './ui/textarea'
import { ArrowLeft } from 'lucide-react'

interface Payment {
  id: string
  memberId: string
  amount: number
  type: string
  date: string
  description: string
}

interface PaymentFormProps {
  payment?: Payment
  memberId?: string
  memberName?: string
  onBack: () => void
  onSubmit: (paymentData: Omit<Payment, 'id'>) => void
}

export function PaymentForm({ payment, memberId, memberName, onBack, onSubmit }: PaymentFormProps) {
  const [formData, setFormData] = useState({
    memberId: memberId || '',
    amount: 0,
    type: 'Tithe',
    date: new Date().toISOString().split('T')[0],
    description: ''
  })

  useEffect(() => {
    if (payment) {
      setFormData({
        memberId: payment.memberId,
        amount: payment.amount,
        type: payment.type,
        date: payment.date.split('T')[0],
        description: payment.description
      })
    }
  }, [payment])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSubmit(formData)
  }

  const handleChange = (field: string, value: string | number) => {
    setFormData(prev => ({ ...prev, [field]: value }))
  }

  const paymentTypes = [
    'Tithe',
    'Offering',
    'Building Fund',
    'Mission',
    'Special Offering',
    'Youth Ministry',
    'Music Ministry',
    'Other'
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center space-x-4">
        <Button variant="ghost" onClick={onBack}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back
        </Button>
      </div>

      <Card className="max-w-2xl">
        <CardHeader>
          <CardTitle>
            {payment ? 'Edit Payment' : 'Add New Payment'}
            {memberName && <span className="text-base font-normal text-gray-600"> for {memberName}</span>}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="amount">Amount ($)</Label>
                <Input
                  id="amount"
                  type="number"
                  step="0.01"
                  min="0"
                  value={formData.amount}
                  onChange={(e) => handleChange('amount', parseFloat(e.target.value) || 0)}
                  placeholder="0.00"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="type">Payment Type</Label>
                <Select value={formData.type} onValueChange={(value) => handleChange('type', value)}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {paymentTypes.map(type => (
                      <SelectItem key={type} value={type}>{type}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="date">Date</Label>
                <Input
                  id="date"
                  type="date"
                  value={formData.date}
                  onChange={(e) => handleChange('date', e.target.value)}
                  required
                />
              </div>

              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="description">Description (Optional)</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => handleChange('description', e.target.value)}
                  placeholder="Add any notes about this payment..."
                  rows={3}
                />
              </div>
            </div>

            <div className="flex space-x-4 pt-4">
              <Button type="submit">
                {payment ? 'Update Payment' : 'Add Payment'}
              </Button>
              <Button type="button" variant="outline" onClick={onBack}>
                Cancel
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}