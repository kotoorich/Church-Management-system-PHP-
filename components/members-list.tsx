import { useState } from 'react'
import { Button } from './ui/button'
import { Card, CardContent, CardHeader, CardTitle } from './ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './ui/table'
import { Badge } from './ui/badge'
import { Plus, Eye, Edit, Trash2 } from 'lucide-react'

interface Member {
  id: string
  name: string
  email: string
  phone: string
  address: string
  membershipDate: string
  status: 'active' | 'inactive'
}

interface MembersListProps {
  members: Member[]
  onAddMember: () => void
  onViewMember: (member: Member) => void
  onEditMember: (member: Member) => void
  onDeleteMember: (memberId: string) => void
  payments: any[]
}

export function MembersList({ 
  members, 
  onAddMember, 
  onViewMember, 
  onEditMember, 
  onDeleteMember,
  payments 
}: MembersListProps) {
  const getMemberPaymentCount = (memberId: string) => {
    return payments.filter(payment => payment.memberId === memberId).length
  }

  const getMemberTotalDonations = (memberId: string) => {
    return payments
      .filter(payment => payment.memberId === memberId)
      .reduce((sum, payment) => sum + payment.amount, 0)
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl mb-2">Church Members</h2>
          <p className="text-gray-600">Manage your church community</p>
        </div>
        <Button onClick={onAddMember}>
          <Plus className="mr-2 h-4 w-4" />
          Add Member
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Member Directory ({members.length} members)</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Payments</TableHead>
                <TableHead>Total Donations</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {members.map((member) => (
                <TableRow key={member.id}>
                  <TableCell className="font-medium">{member.name}</TableCell>
                  <TableCell>{member.email}</TableCell>
                  <TableCell>{member.phone}</TableCell>
                  <TableCell>
                    <Badge variant={member.status === 'active' ? 'default' : 'secondary'}>
                      {member.status}
                    </Badge>
                  </TableCell>
                  <TableCell>{getMemberPaymentCount(member.id)}</TableCell>
                  <TableCell>${getMemberTotalDonations(member.id).toLocaleString()}</TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end space-x-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onViewMember(member)}
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onEditMember(member)}
                      >
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onDeleteMember(member.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
              {members.length === 0 && (
                <TableRow>
                  <TableCell colSpan={7} className="text-center py-8">
                    <p className="text-gray-500">No members found. Add your first member to get started.</p>
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