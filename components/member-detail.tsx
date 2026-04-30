import { useState } from 'react'
import { Button } from './ui/button'
import { Card, CardContent, CardHeader, CardTitle } from './ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './ui/table'
import { Badge } from './ui/badge'
import { ArrowLeft, Plus, Edit, Trash2 } from 'lucide-react'

interface Member {
  id: string
  name: string
  email: string
  phone: string
  address: string
  membershipDate: string
  status: 'active' | 'inactive'
}

interface Payment {
  id: string
  memberId: string
  amount: number
  type: string
  date: string
  description: string
}

interface MemberDetailProps {
  member: Member
  payments: Payment[]
  onBack: () => void
  onAddPayment: (memberId: string) => void
  onEditPayment: (payment: Payment) => void
  onDeletePayment: (paymentId: string) => void
}

export function MemberDetail({ 
  member, 
  payments, 
  onBack, 
  onAddPayment, 
  onEditPayment, 
  onDeletePayment 
}: MemberDetailProps) {
  const memberPayments = payments.filter(payment => payment.memberId === member.id)
  const totalDonations = memberPayments.reduce((sum, payment) => sum + payment.amount, 0)

  return (
    <div className="space-y-6">
      <div className="flex items-center space-x-4">
        <Button variant="ghost" onClick={onBack}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to Members
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Member Info */}
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Member Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                {member.name.split(' ').map(n => n[0]).join('')}
              </div>
              <div>
                <h3 className="font-medium">{member.name}</h3>
                <Badge variant={member.status === 'active' ? 'default' : 'secondary'}>
                  {member.status}
                </Badge>
              </div>
            </div>
            
            <div className="space-y-2">
              <div>
                <label className="text-sm text-gray-600">Email</label>
                <p>{member.email}</p>
              </div>
              <div>
                <label className="text-sm text-gray-600">Phone</label>
                <p>{member.phone}</p>
              </div>
              <div>
                <label className="text-sm text-gray-600">Address</label>
                <p>{member.address}</p>
              </div>
              <div>
                <label className="text-sm text-gray-600">Member Since</label>
                <p>{new Date(member.membershipDate).toLocaleDateString()}</p>
              </div>
            </div>

            <div className="pt-4 border-t">
              <div className="text-center">
                <p className="text-2xl font-bold">${totalDonations.toLocaleString()}</p>
                <p className="text-sm text-gray-600">Total Donations</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Payment History */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Payment History ({memberPayments.length} payments)</CardTitle>
            <Button onClick={() => onAddPayment(member.id)}>
              <Plus className="mr-2 h-4 w-4" />
              Add Payment
            </Button>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {memberPayments
                  .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
                  .map((payment) => (
                  <TableRow key={payment.id}>
                    <TableCell>{new Date(payment.date).toLocaleDateString()}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{payment.type}</Badge>
                    </TableCell>
                    <TableCell>${payment.amount.toLocaleString()}</TableCell>
                    <TableCell>{payment.description}</TableCell>
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
                {memberPayments.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8">
                      <p className="text-gray-500">No payments recorded yet. Add the first payment for this member.</p>
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}