import React, { useState } from 'react'

interface Member {
  id: string
  name: string
  email: string
  phone: string
  profession: string
  digitalAddress: string
  houseAddress: string
  membershipDate: string
  status: 'active' | 'inactive'
  imageUrl?: string
}

interface Payment {
  id: string
  memberId: string
  amount: number
  type: string
  paymentMethod: string
  date: string
  description: string
}

// Dashboard Component
const Dashboard: React.FC<{ members: Member[], payments: Payment[] }> = ({ members, payments }) => {
  const totalMembers = members.length
  const totalPayments = payments.reduce((sum, payment) => sum + payment.amount, 0)
  const thisMonthPayments = payments.filter(payment => {
    const paymentDate = new Date(payment.date)
    const now = new Date()
    return paymentDate.getMonth() === now.getMonth() && paymentDate.getFullYear() === now.getFullYear()
  }).length

  const getMemberName = (memberId: string) => {
    const member = members.find(m => m.id === memberId)
    return member ? member.name : 'Unknown Member'
  }

  const stats = [
    { title: 'Total Members', value: totalMembers, description: 'Active church members' },
    { title: 'Total Donations', value: `₵${totalPayments.toLocaleString()}`, description: 'All time donations' },
    { title: 'This Month', value: thisMonthPayments, description: 'Payments this month' },
    { title: 'Average Donation', value: payments.length > 0 ? `₵${(totalPayments / payments.length).toFixed(0)}` : '₵0', description: 'Per transaction' }
  ]

  return (
    <div className="space-y-6">
      <div>
        <h2>Church Dashboard</h2>
        <p className="text-muted-foreground">Overview of church activities and finances</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat, index) => (
          <div key={index} className="bg-card rounded-lg border p-6 shadow-sm">
            <h3 className="text-sm text-muted-foreground mb-2">{stat.title}</h3>
            <div className="text-2xl mb-1">{stat.value}</div>
            <p className="text-xs text-muted-foreground">{stat.description}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-card rounded-lg border shadow-sm">
          <div className="p-6 border-b">
            <h3>Recent Members</h3>
          </div>
          <div className="p-6">
            <div className="space-y-3">
              {members.slice(-5).map((member) => (
                <div key={member.id} className="flex items-center space-x-3">
                  {member.imageUrl ? (
                    <img
                      src={member.imageUrl}
                      alt={member.name}
                      className="w-8 h-8 rounded-full object-cover"
                    />
                  ) : (
                    <div className="w-8 h-8 bg-muted rounded-full flex items-center justify-center text-sm">
                      {member.name.split(' ').map((n: string) => n[0]).join('')}
                    </div>
                  )}
                  <div>
                    <p>{member.name}</p>
                    <p className="text-sm text-muted-foreground">{member.email}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="bg-card rounded-lg border shadow-sm">
          <div className="p-6 border-b">
            <h3>Recent Payments</h3>
          </div>
          <div className="p-6">
            <div className="space-y-3">
              {payments.slice(-5).map((payment) => (
                <div key={payment.id} className="flex items-center justify-between">
                  <div>
                    <p>{getMemberName(payment.memberId)}</p>
                    <p className="text-sm text-muted-foreground">{payment.type}</p>
                  </div>
                  <div className="text-right">
                    <p>₵{payment.amount}</p>
                    <p className="text-sm text-muted-foreground">{new Date(payment.date).toLocaleDateString()}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

// Members List Component
const MembersList: React.FC<{
  members: Member[]
  payments: Payment[]
  onAddMember: () => void
  onViewMember: (member: Member) => void
  onEditMember: (member: Member) => void
  onDeleteMember: (memberId: string) => void
}> = ({ members, payments, onAddMember, onViewMember, onEditMember, onDeleteMember }) => {
  const [searchTerm, setSearchTerm] = useState('')
  const [currentPage, setCurrentPage] = useState(1)
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all')
  const itemsPerPage = 6

  const getMemberPayments = (memberId: string) => {
    return payments.filter(payment => payment.memberId === memberId)
  }

  const getTotalDonations = (memberId: string) => {
    return getMemberPayments(memberId).reduce((sum, payment) => sum + payment.amount, 0)
  }

  // Filter and search members
  const filteredMembers = members.filter(member => {
    const matchesSearch = member.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         member.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         member.phone.includes(searchTerm)
    const matchesStatus = statusFilter === 'all' || member.status === statusFilter
    return matchesSearch && matchesStatus
  })

  // Pagination
  const totalPages = Math.ceil(filteredMembers.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const currentMembers = filteredMembers.slice(startIndex, endIndex)

  const handlePageChange = (page: number) => {
    setCurrentPage(page)
  }

  const renderPaginationButtons = () => {
    const buttons = []
    const maxVisiblePages = 5
    
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2))
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1)
    
    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1)
    }

    // Previous button
    if (currentPage > 1) {
      buttons.push(
        <button
          key="prev"
          onClick={() => handlePageChange(currentPage - 1)}
          className="px-3 py-2 text-sm border rounded-lg hover:bg-accent transition-colors"
        >
          ←
        </button>
      )
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
      buttons.push(
        <button
          key={i}
          onClick={() => handlePageChange(i)}
          className={`px-3 py-2 text-sm border rounded-lg transition-colors ${
            currentPage === i 
              ? 'bg-primary text-primary-foreground' 
              : 'hover:bg-accent'
          }`}
        >
          {i}
        </button>
      )
    }

    // Next button
    if (currentPage < totalPages) {
      buttons.push(
        <button
          key="next"
          onClick={() => handlePageChange(currentPage + 1)}
          className="px-3 py-2 text-sm border rounded-lg hover:bg-accent transition-colors"
        >
          →
        </button>
      )
    }

    return buttons
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2>Church Members</h2>
          <p className="text-muted-foreground">Manage your church community</p>
        </div>
        <button 
          onClick={onAddMember}
          className="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2"
        >
          <span>+</span> Add Member
        </button>
      </div>

      {/* Search and Filter Bar */}
      <div className="bg-card rounded-lg border shadow-sm p-6">
        <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
          <div className="flex-1 max-w-md">
            <div className="relative">
              <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">
                🔍
              </span>
              <input
                type="text"
                placeholder="Search members by name, email, or phone..."
                value={searchTerm}
                onChange={(e) => {
                  setSearchTerm(e.target.value)
                  setCurrentPage(1) // Reset to first page when searching
                }}
                className="w-full pl-10 pr-4 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
              />
            </div>
          </div>
          
          <div className="flex items-center gap-4">
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value as 'all' | 'active' | 'inactive')
                setCurrentPage(1)
              }}
              className="px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
            >
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            
            <div className="text-sm text-muted-foreground">
              {filteredMembers.length} of {members.length} members
            </div>
          </div>
        </div>
      </div>

      {/* Members Grid */}
      <div className="bg-card rounded-lg border shadow-sm">
        <div className="p-6 border-b">
          <h3>Member Directory</h3>
        </div>
        <div className="p-6">
          {currentMembers.length > 0 ? (
            <div className="grid gap-4">
              {currentMembers.map((member) => (
                <div 
                  key={member.id} 
                  className="flex items-center justify-between p-4 border rounded-lg hover:shadow-md transition-shadow cursor-pointer"
                  onClick={() => onViewMember(member)}
                >
                  <div className="flex items-center space-x-4">
                    {member.imageUrl ? (
                      <img
                        src={member.imageUrl}
                        alt={member.name}
                        className="w-12 h-12 rounded-full object-cover border-2 border-primary/20"
                      />
                    ) : (
                      <div className="w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary font-medium">
                        {member.name.split(' ').map(n => n[0]).join('')}
                      </div>
                    )}
                    <div>
                      <h4 className="hover:text-primary transition-colors">{member.name}</h4>
                      <p className="text-sm text-muted-foreground">{member.email}</p>
                      <p className="text-sm text-muted-foreground">{member.phone}</p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-6">
                    <span className={`px-3 py-1 text-xs rounded-full font-medium ${
                      member.status === 'active' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                        : 'bg-muted text-muted-foreground'
                    }`}>
                      {member.status}
                    </span>
                    <div className="text-right text-sm">
                      <p className="font-medium">{getMemberPayments(member.id).length} payments</p>
                      <p className="text-muted-foreground">₵{getTotalDonations(member.id).toLocaleString()}</p>
                    </div>
                    <div className="flex space-x-1" onClick={(e) => e.stopPropagation()}>
                      <button 
                        onClick={() => onViewMember(member)}
                        className="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors"
                        title="View Details"
                      >
                        👁
                      </button>
                      <button 
                        onClick={() => onEditMember(member)}
                        className="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors"
                        title="Edit Member"
                      >
                        ✏️
                      </button>
                      <button 
                        onClick={() => onDeleteMember(member.id)}
                        className="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
                        title="Delete Member"
                      >
                        🗑
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <div className="text-6xl mb-4">👥</div>
              <h3 className="text-lg mb-2">No members found</h3>
              <p className="text-muted-foreground mb-4">
                {searchTerm || statusFilter !== 'all' 
                  ? 'Try adjusting your search criteria' 
                  : 'Add your first member to get started'}
              </p>
              {(!searchTerm && statusFilter === 'all') && (
                <button 
                  onClick={onAddMember}
                  className="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors"
                >
                  Add First Member
                </button>
              )}
            </div>
          )}
        </div>
        
        {/* Pagination */}
        {totalPages > 1 && (
          <div className="p-6 border-t">
            <div className="flex items-center justify-between">
              <div className="text-sm text-muted-foreground">
                Showing {startIndex + 1} to {Math.min(endIndex, filteredMembers.length)} of {filteredMembers.length} members
              </div>
              <div className="flex items-center space-x-2">
                {renderPaginationButtons()}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

// Member Detail Component
const MemberDetail: React.FC<{
  member: Member
  payments: Payment[]
  members: Member[]
  onBack: () => void
  onAddPayment: (memberId: string) => void
  onEditPayment: (payment: Payment) => void
  onDeletePayment: (paymentId: string) => void
}> = ({ member, payments, members, onBack, onAddPayment, onEditPayment, onDeletePayment }) => {
  const [activeTab, setActiveTab] = useState<'overview' | 'payments' | 'monthly'>('overview')
  const [sortField, setSortField] = useState<'date' | 'amount' | 'type'>('date')
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc')
  const [filterType, setFilterType] = useState<string>('all')
  
  const memberPayments = payments.filter(payment => payment.memberId === member.id)
  const totalDonations = memberPayments.reduce((sum, payment) => sum + payment.amount, 0)
  const avgDonation = memberPayments.length > 0 ? totalDonations / memberPayments.length : 0
  
  // Get unique payment types for filter
  const paymentTypes = [...new Set(memberPayments.map(p => p.type))]

  // Filter and sort payments
  const filteredPayments = memberPayments
    .filter(payment => filterType === 'all' || payment.type === filterType)
    .sort((a, b) => {
      let aValue: any, bValue: any
      
      switch (sortField) {
        case 'date':
          aValue = new Date(a.date).getTime()
          bValue = new Date(b.date).getTime()
          break
        case 'amount':
          aValue = a.amount
          bValue = b.amount
          break
        case 'type':
          aValue = a.type.toLowerCase()
          bValue = b.type.toLowerCase()
          break
        default:
          return 0
      }
      
      if (sortDirection === 'asc') {
        return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
      } else {
        return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
      }
    })

  const handleSort = (field: 'date' | 'amount' | 'type') => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortDirection('desc')
    }
  }

  const getSortIcon = (field: 'date' | 'amount' | 'type') => {
    if (sortField !== field) return '↕️'
    return sortDirection === 'asc' ? '↑' : '↓'
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <button 
          onClick={onBack}
          className="flex items-center gap-2 text-primary hover:text-primary/80 transition-colors px-3 py-2 rounded-lg hover:bg-accent"
        >
          ← Back to Members
        </button>
        
        <div className="flex items-center gap-3">
          <button 
            onClick={() => onAddPayment(member.id)}
            className="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2"
          >
            <span>+</span> Add Payment
          </button>
        </div>
      </div>
      
      {/* Navigation Tabs */}
      <div className="bg-card rounded-lg border shadow-sm">
        <div className="flex border-b">
          <button
            onClick={() => setActiveTab('overview')}
            className={`px-6 py-3 font-medium transition-colors ${
              activeTab === 'overview'
                ? 'text-primary border-b-2 border-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            📋 Overview
          </button>
          <button
            onClick={() => setActiveTab('payments')}
            className={`px-6 py-3 font-medium transition-colors ${
              activeTab === 'payments'
                ? 'text-primary border-b-2 border-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            💰 Payment History
          </button>
          <button
            onClick={() => setActiveTab('monthly')}
            className={`px-6 py-3 font-medium transition-colors ${
              activeTab === 'monthly'
                ? 'text-primary border-b-2 border-primary'
                : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            📅 Monthly Tracker
          </button>
        </div>
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && (
        <>
          {/* Member Profile Header */}
          <div className="bg-gradient-to-r from-primary/5 to-primary/10 rounded-lg border p-6">
            <div className="flex items-center space-x-6">
              {member.imageUrl ? (
                <img
                  src={member.imageUrl}
                  alt={member.name}
                  className="w-20 h-20 rounded-full object-cover border-4 border-primary/20"
                />
              ) : (
                <div className="w-20 h-20 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary text-2xl font-medium">
                  {member.name.split(' ').map(n => n[0]).join('')}
                </div>
              )}
              <div className="flex-1">
                <h2 className="text-2xl mb-2">{member.name}</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                  <div>
                    <label className="text-muted-foreground">Email</label>
                    <p>{member.email}</p>
                  </div>
                  <div>
                    <label className="text-muted-foreground">Phone</label>
                    <p>{member.phone}</p>
                  </div>
                  <div>
                    <label className="text-muted-foreground">Profession</label>
                    <p>{member.profession}</p>
                  </div>
                  <div>
                    <label className="text-muted-foreground">Status</label>
                    <span className={`inline-block px-3 py-1 text-xs rounded-full font-medium ${
                      member.status === 'active' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                        : 'bg-muted text-muted-foreground'
                    }`}>
                      {member.status}
                    </span>
                  </div>
                  <div>
                    <label className="text-muted-foreground">Member Since</label>
                    <p>{new Date(member.membershipDate).toLocaleDateString()}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="mt-6 pt-6 border-t">
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div className="text-center">
                  <p className="text-2xl font-bold text-primary">{memberPayments.length}</p>
                  <p className="text-sm text-muted-foreground">Total Payments</p>
                </div>
                <div className="text-center">
                  <p className="text-2xl font-bold text-primary">₵{totalDonations.toLocaleString()}</p>
                  <p className="text-sm text-muted-foreground">Total Donated</p>
                </div>
                <div className="text-center">
                  <p className="text-2xl font-bold text-primary">₵{avgDonation.toFixed(0)}</p>
                  <p className="text-sm text-muted-foreground">Average Payment</p>
                </div>
                <div className="text-center">
                  <p className="text-2xl font-bold text-primary">{paymentTypes.length}</p>
                  <p className="text-sm text-muted-foreground">Payment Types</p>
                </div>
              </div>
            </div>
          </div>

          {/* Address Card */}
          <div className="bg-card rounded-lg border shadow-sm p-6">
            <h3 className="mb-3">Address Information</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="text-muted-foreground text-sm">Digital Address (GPS)</label>
                <p className="font-medium">{member.digitalAddress || 'Not provided'}</p>
              </div>
              <div>
                <label className="text-muted-foreground text-sm">House Address</label>
                <p className="font-medium">{member.houseAddress}</p>
              </div>
            </div>
          </div>
        </>
      )}

      {activeTab === 'payments' && (
        /* Payment History - Excel Style Table */
        <div className="bg-card rounded-lg border shadow-sm">
          <div className="p-6 border-b">
            <div className="flex items-center justify-between mb-4">
              <h3>Payment History ({filteredPayments.length} payments)</h3>
              
              <div className="flex items-center gap-4">
                <select
                  value={filterType}
                  onChange={(e) => setFilterType(e.target.value)}
                  className="px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring text-sm"
                >
                  <option value="all">All Types</option>
                  {paymentTypes.map(type => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          {filteredPayments.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-muted/50">
                  <tr>
                    <th className="text-left p-4 border-b font-medium">
                      <button 
                        onClick={() => handleSort('date')}
                        className="flex items-center gap-2 hover:text-primary transition-colors"
                      >
                        Date {getSortIcon('date')}
                      </button>
                    </th>
                    <th className="text-left p-4 border-b font-medium">
                      <button 
                        onClick={() => handleSort('type')}
                        className="flex items-center gap-2 hover:text-primary transition-colors"
                      >
                        Type {getSortIcon('type')}
                      </button>
                    </th>
                    <th className="text-left p-4 border-b font-medium">Method</th>
                    <th className="text-right p-4 border-b font-medium">
                      <button 
                        onClick={() => handleSort('amount')}
                        className="flex items-center justify-end gap-2 hover:text-primary transition-colors w-full"
                      >
                        Amount {getSortIcon('amount')}
                      </button>
                    </th>
                    <th className="text-left p-4 border-b font-medium">Description</th>
                    <th className="text-center p-4 border-b font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredPayments.map((payment, index) => (
                    <tr 
                      key={payment.id} 
                      className={`hover:bg-accent/50 transition-colors ${
                        index % 2 === 0 ? 'bg-background' : 'bg-muted/20'
                      }`}
                    >
                      <td className="p-4 border-b">
                        <div className="font-medium">
                          {new Date(payment.date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                          })}
                        </div>
                        <div className="text-xs text-muted-foreground">
                          {new Date(payment.date).toLocaleDateString('en-US', { weekday: 'long' })}
                        </div>
                      </td>
                      <td className="p-4 border-b">
                        <span className="inline-block px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                          {payment.type}
                        </span>
                      </td>
                      <td className="p-4 border-b">
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-secondary/50 text-secondary-foreground rounded text-sm">
                          {payment.paymentMethod === 'Cash' && '💵'}
                          {payment.paymentMethod === 'Mobile Money' && '📱'}
                          {payment.paymentMethod === 'Bank Transfer' && '🏦'}
                          {payment.paymentMethod === 'Check' && '📄'}
                          {payment.paymentMethod === 'Card' && '💳'}
                          {payment.paymentMethod === 'Other' && '🔄'}
                          {payment.paymentMethod}
                        </span>
                      </td>
                      <td className="p-4 border-b text-right">
                        <span className="font-bold text-lg">₵{payment.amount.toLocaleString()}</span>
                      </td>
                      <td className="p-4 border-b">
                        <span className="text-muted-foreground">
                          {payment.description || '—'}
                        </span>
                      </td>
                      <td className="p-4 border-b text-center">
                        <div className="flex items-center justify-center space-x-2">
                          <button 
                            onClick={() => onEditPayment(payment)}
                            className="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors"
                            title="Edit Payment"
                          >
                            ✏️
                          </button>
                          <button 
                            onClick={() => onDeletePayment(payment.id)}
                            className="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
                            title="Delete Payment"
                          >
                            🗑
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot className="bg-muted/30">
                  <tr>
                    <td colSpan={3} className="p-4 border-t font-medium">
                      Total ({filteredPayments.length} payments)
                    </td>
                    <td className="p-4 border-t text-right font-bold text-lg">
                      ₵{filteredPayments.reduce((sum, p) => sum + p.amount, 0).toLocaleString()}
                    </td>
                    <td colSpan={2} className="p-4 border-t"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          ) : (
            <div className="p-12 text-center">
              <div className="text-6xl mb-4">💰</div>
              <h3 className="text-lg mb-2">No payments found</h3>
              <p className="text-muted-foreground mb-4">
                {filterType !== 'all' 
                  ? 'No payments match the selected filter' 
                  : 'This member has no payment records yet'}
              </p>
              <button 
                onClick={() => onAddPayment(member.id)}
                className="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors"
              >
                Add First Payment
              </button>
            </div>
          )}
        </div>
      )}

      {activeTab === 'monthly' && (
        <div className="space-y-6">
          <MemberMonthlyTracker 
            member={member}
            payments={payments}
            members={members}
          />
        </div>
      )}
    </div>
  )
}

// Member Form Component
const MemberForm: React.FC<{
  member?: Member
  onBack: () => void
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void
}> = ({ member, onBack, onSubmit }) => {
  const [imagePreview, setImagePreview] = useState<string | null>(member?.imageUrl || null)
  const [imageFile, setImageFile] = useState<File | null>(null)
  const [professionInput, setProfessionInput] = useState(member?.profession || '')
  const [showProfessionSuggestions, setShowProfessionSuggestions] = useState(false)
  
  // Common professions for autocomplete
  const commonProfessions = [
    'Teacher', 'Engineer', 'Doctor', 'Nurse', 'Lawyer', 'Accountant', 'Pastor', 'Business Owner',
    'Farmer', 'Banker', 'Police Officer', 'Carpenter', 'Electrician', 'Chef', 'Driver',
    'Student', 'Retired', 'Civil Servant', 'Trader', 'Mechanic', 'Tailor', 'Hair Dresser',
    'Pharmacist', 'Architect', 'Software Developer', 'Marketing Manager', 'Sales Representative'
  ]
  
  const filteredProfessions = commonProfessions.filter(profession =>
    profession.toLowerCase().includes(professionInput.toLowerCase())
  ).slice(0, 8)

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setImageFile(file)
      const reader = new FileReader()
      reader.onload = (e) => {
        setImagePreview(e.target?.result as string)
      }
      reader.readAsDataURL(file)
    }
  }

  const removeImage = () => {
    setImagePreview(null)
    setImageFile(null)
    const fileInput = document.getElementById('memberImage') as HTMLInputElement
    if (fileInput) fileInput.value = ''
  }

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    
    // Extract form data directly
    const formData = new FormData(e.currentTarget)
    let imageUrl = imagePreview || member?.imageUrl || ''
    
    // Handle image conversion if needed
    if (imageFile) {
      try {
        const base64 = await new Promise<string>((resolve, reject) => {
          const reader = new FileReader()
          reader.onload = () => resolve(reader.result as string)
          reader.onerror = reject
          reader.readAsDataURL(imageFile)
        })
        imageUrl = base64
      } catch (error) {
        console.error('Error converting image:', error)
        alert('Error processing image. Please try again.')
        return
      }
    }
    
    // Create a proper mock event with all necessary methods
    const mockEvent = {
      ...e,
      preventDefault: () => {}, // Provide a dummy preventDefault function
      currentTarget: {
        ...e.currentTarget,
        elements: {
          name: { value: formData.get('name') as string },
          email: { value: formData.get('email') as string },
          phone: { value: formData.get('phone') as string },
          profession: { value: formData.get('profession') as string },
          digitalAddress: { value: formData.get('digitalAddress') as string },
          houseAddress: { value: formData.get('houseAddress') as string },
          membershipDate: { value: formData.get('membershipDate') as string },
          status: { value: formData.get('status') as string },
          imageUrl: { value: imageUrl }
        }
      }
    } as React.FormEvent<HTMLFormElement>
    
    onSubmit(mockEvent)
  }

  return (
    <div className="space-y-6">
      <button 
        onClick={onBack}
        className="text-primary hover:text-primary/80 transition-colors"
      >
        ← Back to Members
      </button>

      <div className="max-w-2xl bg-card rounded-lg border shadow-sm">
        <div className="p-6 border-b">
          <h3>{member ? 'Edit Member' : 'Add New Member'}</h3>
        </div>
        <div className="p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Profile Image Section */}
            <div className="flex flex-col items-center space-y-4">
              <div className="relative">
                {imagePreview ? (
                  <div className="relative">
                    <img
                      src={imagePreview}
                      alt="Member preview"
                      className="w-32 h-32 rounded-full object-cover border-4 border-primary/20"
                    />
                    <button
                      type="button"
                      onClick={removeImage}
                      className="absolute -top-2 -right-2 bg-destructive text-destructive-foreground rounded-full w-8 h-8 flex items-center justify-center hover:bg-destructive/90 transition-colors"
                    >
                      ×
                    </button>
                  </div>
                ) : (
                  <div className="w-32 h-32 rounded-full bg-gradient-to-br from-primary/20 to-primary/40 flex items-center justify-center text-primary text-2xl font-medium border-4 border-primary/20">
                    {member?.name ? member.name.split(' ').map(n => n[0]).join('') : '👤'}
                  </div>
                )}
              </div>
              
              <div className="text-center">
                <label
                  htmlFor="memberImage"
                  className="inline-flex items-center px-4 py-2 bg-secondary text-secondary-foreground rounded-lg hover:bg-secondary/80 cursor-pointer transition-colors"
                >
                  📷 {imagePreview ? 'Change Photo' : 'Add Photo'}
                </label>
                <input
                  id="memberImage"
                  type="file"
                  accept="image/*"
                  onChange={handleImageChange}
                  className="hidden"
                />
                <p className="text-xs text-muted-foreground mt-2">
                  JPG, PNG, or GIF (max 5MB)
                </p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label htmlFor="name" className="block text-sm text-muted-foreground mb-1">Full Name</label>
                <input
                  id="name"
                  name="name"
                  type="text"
                  defaultValue={member?.name || ''}
                  required
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>

              <div>
                <label htmlFor="email" className="block text-sm text-muted-foreground mb-1">Email</label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  defaultValue={member?.email || ''}
                  required
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>

              <div>
                <label htmlFor="phone" className="block text-sm text-muted-foreground mb-1">Phone Number</label>
                <input
                  id="phone"
                  name="phone"
                  type="text"
                  defaultValue={member?.phone || ''}
                  required
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>

              <div className="relative">
                <label htmlFor="profession" className="block text-sm text-muted-foreground mb-1">Profession/Occupation</label>
                <input
                  id="profession"
                  name="profession"
                  type="text"
                  value={professionInput}
                  onChange={(e) => setProfessionInput(e.target.value)}
                  onFocus={() => setShowProfessionSuggestions(true)}
                  onBlur={() => setTimeout(() => setShowProfessionSuggestions(false), 200)}
                  placeholder="Start typing your profession..."
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
                
                {/* Profession Suggestions Dropdown */}
                {showProfessionSuggestions && professionInput && filteredProfessions.length > 0 && (
                  <div className="absolute top-full left-0 right-0 mt-1 bg-card border rounded-lg shadow-lg z-20 max-h-48 overflow-y-auto">
                    {filteredProfessions.map((profession, index) => (
                      <button
                        key={profession}
                        type="button"
                        onClick={() => {
                          setProfessionInput(profession)
                          setShowProfessionSuggestions(false)
                        }}
                        className="w-full text-left px-3 py-2 hover:bg-accent transition-colors first:rounded-t-lg last:rounded-b-lg"
                      >
                        {profession}
                      </button>
                    ))}
                  </div>
                )}
              </div>

              <div>
                <label htmlFor="status" className="block text-sm text-muted-foreground mb-1">Status</label>
                <select 
                  id="status"
                  name="status"
                  defaultValue={member?.status || 'active'}
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>

              <div>
                <label htmlFor="digitalAddress" className="block text-sm text-muted-foreground mb-1">Digital Address (GPS)</label>
                <input
                  id="digitalAddress"
                  name="digitalAddress"
                  type="text"
                  defaultValue={member?.digitalAddress || ''}
                  placeholder="e.g., GA-123-4567"
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>

              <div>
                <label htmlFor="houseAddress" className="block text-sm text-muted-foreground mb-1">House Address</label>
                <input
                  id="houseAddress"
                  name="houseAddress"
                  type="text"
                  defaultValue={member?.houseAddress || ''}
                  placeholder="e.g., House 123, Street Name"
                  required
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>

              <div>
                <label htmlFor="membershipDate" className="block text-sm text-muted-foreground mb-1">Membership Date</label>
                <input
                  id="membershipDate"
                  name="membershipDate"
                  type="date"
                  defaultValue={member?.membershipDate.split('T')[0] || new Date().toISOString().split('T')[0]}
                  required
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>
            </div>

            <div className="flex space-x-4 pt-4">
              <button 
                type="submit"
                className="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition-colors"
              >
                {member ? 'Update Member' : 'Add Member'}
              </button>
              <button 
                type="button" 
                onClick={onBack}
                className="bg-secondary text-secondary-foreground px-6 py-2 rounded-lg hover:bg-secondary/80 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}

// Member Search Selector Component
const MemberSearchSelector: React.FC<{
  members: Member[]
  selectedMemberId: string
  onMemberSelect: (memberId: string) => void
}> = ({ members, selectedMemberId, onMemberSelect }) => {
  const [searchTerm, setSearchTerm] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  
  const selectedMember = members.find(m => m.id === selectedMemberId)
  
  const filteredMembers = members
    .filter(m => m.status === 'active')
    .filter(member => 
      member.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      member.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      member.phone.includes(searchTerm)
    )
    .sort((a, b) => a.name.localeCompare(b.name))

  const handleMemberSelect = (member: Member) => {
    onMemberSelect(member.id)
    setIsOpen(false)
    setSearchTerm('')
  }

  return (
    <div className="relative">
      <input
        name="memberId"
        type="hidden"
        value={selectedMemberId}
      />
      
      {/* Search Input */}
      <div className="relative">
        <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground z-10">
          🔍
        </span>
        <input
          type="text"
          placeholder="Search members by name, email, or phone..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          onFocus={() => setIsOpen(true)}
          className="w-full pl-10 pr-4 py-3 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
        />
      </div>

      {/* Selected Member Display */}
      {selectedMember && !isOpen && (
        <div className="mt-3 flex items-center space-x-3 p-3 bg-card rounded-lg border">
          {selectedMember.imageUrl ? (
            <img
              src={selectedMember.imageUrl}
              alt={selectedMember.name}
              className="w-12 h-12 rounded-full object-cover"
            />
          ) : (
            <div className="w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary font-medium">
              {selectedMember.name.split(' ').map(n => n[0]).join('')}
            </div>
          )}
          <div className="flex-1">
            <p className="font-medium">{selectedMember.name}</p>
            <p className="text-sm text-muted-foreground">{selectedMember.email}</p>
            <p className="text-sm text-muted-foreground">{selectedMember.phone}</p>
          </div>
          <button
            type="button"
            onClick={() => {
              onMemberSelect('')
              setSearchTerm('')
            }}
            className="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
            title="Remove member"
          >
            ×
          </button>
        </div>
      )}

      {/* Dropdown List */}
      {isOpen && (searchTerm || !selectedMember) && (
        <>
          {/* Backdrop */}
          <div 
            className="fixed inset-0 z-10" 
            onClick={() => setIsOpen(false)}
          />
          
          {/* Results */}
          <div className="absolute top-full left-0 right-0 mt-1 bg-card border rounded-lg shadow-lg z-20 max-h-60 overflow-y-auto">
            {filteredMembers.length > 0 ? (
              <div className="p-2">
                {filteredMembers.map((member) => (
                  <button
                    key={member.id}
                    type="button"
                    onClick={() => handleMemberSelect(member)}
                    className="w-full flex items-center space-x-3 p-3 hover:bg-accent rounded-lg transition-colors text-left"
                  >
                    {member.imageUrl ? (
                      <img
                        src={member.imageUrl}
                        alt={member.name}
                        className="w-10 h-10 rounded-full object-cover"
                      />
                    ) : (
                      <div className="w-10 h-10 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary font-medium text-sm">
                        {member.name.split(' ').map(n => n[0]).join('')}
                      </div>
                    )}
                    <div className="flex-1">
                      <p className="font-medium">{member.name}</p>
                      <p className="text-sm text-muted-foreground">{member.email}</p>
                      <p className="text-sm text-muted-foreground">{member.phone}</p>
                    </div>
                  </button>
                ))}
              </div>
            ) : (
              <div className="p-6 text-center text-muted-foreground">
                <p>No members found</p>
                <p className="text-sm">Try adjusting your search term</p>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  )
}

// Payment Form Component
const PaymentForm: React.FC<{
  payment?: Payment
  members: Member[]
  preSelectedMemberId?: string
  onBack: () => void
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void
}> = ({ payment, members, preSelectedMemberId, onBack, onSubmit }) => {
  const [selectedMemberId, setSelectedMemberId] = useState<string>(
    payment?.memberId || preSelectedMemberId || ''
  )
  
  const selectedMember = members.find(m => m.id === selectedMemberId)

  return (
    <div className="space-y-6">
      <button 
        onClick={onBack}
        className="text-primary hover:text-primary/80 transition-colors"
      >
        ← Back
      </button>

      <div className="max-w-2xl bg-card rounded-lg border shadow-sm">
        <div className="p-6 border-b">
          <h3>
            {payment ? 'Edit Payment' : 'Add New Payment'}
            {selectedMember && <span className="text-base font-normal text-muted-foreground"> for {selectedMember.name}</span>}
          </h3>
        </div>
        <div className="p-6">
          <form onSubmit={onSubmit} className="space-y-6">
            {/* Member Selection Section */}
            <div className="bg-muted/30 rounded-lg p-4">
              <label htmlFor="memberId" className="block text-sm text-muted-foreground mb-3">Select Member</label>
              <div className="space-y-3">
                <MemberSearchSelector
                  members={members}
                  selectedMemberId={selectedMemberId}
                  onMemberSelect={setSelectedMemberId}
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label htmlFor="amount" className="block text-sm text-muted-foreground mb-1">Amount (₵)</label>
                <input
                  id="amount"
                  name="amount"
                  type="number"
                  step="0.01"
                  min="0"
                  defaultValue={payment?.amount || ''}
                  required
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>

              <div>
                <label htmlFor="type" className="block text-sm text-muted-foreground mb-1">Payment Type</label>
                <select 
                  id="type"
                  name="type"
                  defaultValue={payment?.type || 'Tithe'}
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                >
                  <option value="Tithe">Tithe</option>
                  <option value="Offering">Offering</option>
                  <option value="Dues">Dues</option>
                  <option value="Building Fund">Building Fund</option>
                  <option value="Mission">Mission</option>
                  <option value="Special Offering">Special Offering</option>
                  <option value="Youth Ministry">Youth Ministry</option>
                  <option value="Music Ministry">Music Ministry</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div>
                <label htmlFor="paymentMethod" className="block text-sm text-muted-foreground mb-1">Payment Method</label>
                <select 
                  id="paymentMethod"
                  name="paymentMethod"
                  defaultValue={payment?.paymentMethod || 'Cash'}
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                >
                  <option value="Cash">💵 Cash</option>
                  <option value="Mobile Money">📱 Mobile Money</option>
                  <option value="Bank Transfer">🏦 Bank Transfer</option>
                  <option value="Check">📄 Check</option>
                  <option value="Card">💳 Card</option>
                  <option value="Other">🔄 Other</option>
                </select>
              </div>

              <div>
                <label htmlFor="date" className="block text-sm text-muted-foreground mb-1">Date</label>
                <input
                  id="date"
                  name="date"
                  type="date"
                  defaultValue={payment?.date.split('T')[0] || new Date().toISOString().split('T')[0]}
                  required
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>

              <div className="md:col-span-3">
                <label htmlFor="description" className="block text-sm text-muted-foreground mb-1">Description (Optional)</label>
                <textarea
                  id="description"
                  name="description"
                  defaultValue={payment?.description || ''}
                  rows={3}
                  placeholder="Add any notes about this payment..."
                  className="w-full px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                />
              </div>
            </div>

            <div className="flex space-x-4 pt-4">
              <button 
                type="submit"
                className="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition-colors"
                disabled={!selectedMemberId}
              >
                {payment ? 'Update Payment' : 'Add Payment'}
              </button>
              <button 
                type="button" 
                onClick={onBack}
                className="bg-secondary text-secondary-foreground px-6 py-2 rounded-lg hover:bg-secondary/80 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}

// Payments List Component
const PaymentsList: React.FC<{
  payments: Payment[]
  members: Member[]
  onAddPayment: () => void
  onEditPayment: (payment: Payment) => void
  onDeletePayment: (paymentId: string) => void
}> = ({ payments, members, onAddPayment, onEditPayment, onDeletePayment }) => {
  const [searchTerm, setSearchTerm] = useState('')
  const [currentPage, setCurrentPage] = useState(1)
  const [typeFilter, setTypeFilter] = useState<string>('all')
  const [sortField, setSortField] = useState<'date' | 'amount' | 'member'>('date')
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc')
  const itemsPerPage = 8
  
  const getMemberName = (memberId: string) => {
    const member = members.find(m => m.id === memberId)
    return member ? member.name : 'Unknown Member'
  }

  const getMember = (memberId: string) => {
    return members.find(m => m.id === memberId)
  }

  // Get unique payment types for filter
  const paymentTypes = [...new Set(payments.map(p => p.type))]

  // Filter and search payments
  const filteredPayments = payments.filter(payment => {
    const member = getMember(payment.memberId)
    const memberName = member ? member.name.toLowerCase() : 'unknown member'
    
    const matchesSearch = memberName.includes(searchTerm.toLowerCase()) ||
                         payment.type.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         payment.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         payment.amount.toString().includes(searchTerm)
    
    const matchesType = typeFilter === 'all' || payment.type === typeFilter
    
    return matchesSearch && matchesType
  })

  // Sort payments
  const sortedPayments = filteredPayments.sort((a, b) => {
    let aValue: any, bValue: any
    
    switch (sortField) {
      case 'date':
        aValue = new Date(a.date).getTime()
        bValue = new Date(b.date).getTime()
        break
      case 'amount':
        aValue = a.amount
        bValue = b.amount
        break
      case 'member':
        aValue = getMemberName(a.memberId).toLowerCase()
        bValue = getMemberName(b.memberId).toLowerCase()
        break
      default:
        return 0
    }
    
    if (sortDirection === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })

  // Pagination
  const totalPages = Math.ceil(sortedPayments.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const currentPayments = sortedPayments.slice(startIndex, endIndex)

  const totalAmount = filteredPayments.reduce((sum, payment) => sum + payment.amount, 0)

  const handlePageChange = (page: number) => {
    setCurrentPage(page)
  }

  const handleSort = (field: 'date' | 'amount' | 'member') => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortDirection('desc')
    }
  }

  const getSortIcon = (field: 'date' | 'amount' | 'member') => {
    if (sortField !== field) return '↕️'
    return sortDirection === 'asc' ? '↑' : '↓'
  }

  const renderPaginationButtons = () => {
    const buttons = []
    const maxVisiblePages = 5
    
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2))
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1)
    
    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1)
    }

    // Previous button
    if (currentPage > 1) {
      buttons.push(
        <button
          key="prev"
          onClick={() => handlePageChange(currentPage - 1)}
          className="px-3 py-2 text-sm border rounded-lg hover:bg-accent transition-colors"
        >
          ←
        </button>
      )
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
      buttons.push(
        <button
          key={i}
          onClick={() => handlePageChange(i)}
          className={`px-3 py-2 text-sm border rounded-lg transition-colors ${
            currentPage === i 
              ? 'bg-primary text-primary-foreground' 
              : 'hover:bg-accent'
          }`}
        >
          {i}
        </button>
      )
    }

    // Next button
    if (currentPage < totalPages) {
      buttons.push(
        <button
          key="next"
          onClick={() => handlePageChange(currentPage + 1)}
          className="px-3 py-2 text-sm border rounded-lg hover:bg-accent transition-colors"
        >
          →
        </button>
      )
    }

    return buttons
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2>Payment Records</h2>
          <p className="text-muted-foreground">Track all church donations and payments</p>
        </div>
        <button 
          onClick={onAddPayment}
          className="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2"
        >
          <span>+</span> Add Payment
        </button>
      </div>

      {/* Search and Filter Bar */}
      <div className="bg-card rounded-lg border shadow-sm p-6">
        <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
          <div className="flex-1 max-w-md">
            <div className="relative">
              <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">
                🔍
              </span>
              <input
                type="text"
                placeholder="Search by member name, type, or amount..."
                value={searchTerm}
                onChange={(e) => {
                  setSearchTerm(e.target.value)
                  setCurrentPage(1) // Reset to first page when searching
                }}
                className="w-full pl-10 pr-4 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
              />
            </div>
          </div>
          
          <div className="flex items-center gap-4">
            <select
              value={typeFilter}
              onChange={(e) => {
                setTypeFilter(e.target.value)
                setCurrentPage(1)
              }}
              className="px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
            >
              <option value="all">All Types</option>
              {paymentTypes.map(type => (
                <option key={type} value={type}>{type}</option>
              ))}
            </select>
            
            <div className="text-sm text-muted-foreground">
              {filteredPayments.length} of {payments.length} payments
            </div>
          </div>
        </div>
      </div>

      {/* Payments Table */}
      <div className="bg-card rounded-lg border shadow-sm">
        <div className="p-6 border-b">
          <div className="flex items-center justify-between">
            <h3>
              Payment Records ({filteredPayments.length} payments)
            </h3>
            <div className="text-right">
              <p className="text-lg font-bold">Total: ₵{totalAmount.toLocaleString()}</p>
              <p className="text-sm text-muted-foreground">
                Showing {startIndex + 1} to {Math.min(endIndex, filteredPayments.length)} of {filteredPayments.length}
              </p>
            </div>
          </div>
        </div>

        {currentPayments.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-muted/50">
                <tr>
                  <th className="text-left p-4 border-b font-medium">
                    <button 
                      onClick={() => handleSort('member')}
                      className="flex items-center gap-2 hover:text-primary transition-colors"
                    >
                      Member {getSortIcon('member')}
                    </button>
                  </th>
                  <th className="text-left p-4 border-b font-medium">
                    <button 
                      onClick={() => handleSort('date')}
                      className="flex items-center gap-2 hover:text-primary transition-colors"
                    >
                      Date {getSortIcon('date')}
                    </button>
                  </th>
                  <th className="text-left p-4 border-b font-medium">Type</th>
                  <th className="text-left p-4 border-b font-medium">Method</th>
                  <th className="text-right p-4 border-b font-medium">
                    <button 
                      onClick={() => handleSort('amount')}
                      className="flex items-center justify-end gap-2 hover:text-primary transition-colors w-full"
                    >
                      Amount {getSortIcon('amount')}
                    </button>
                  </th>
                  <th className="text-left p-4 border-b font-medium">Description</th>
                  <th className="text-center p-4 border-b font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {currentPayments.map((payment, index) => {
                  const member = getMember(payment.memberId)
                  return (
                    <tr 
                      key={payment.id} 
                      className={`hover:bg-accent/50 transition-colors ${
                        index % 2 === 0 ? 'bg-background' : 'bg-muted/20'
                      }`}
                    >
                      <td className="p-4 border-b">
                        <div className="flex items-center space-x-3">
                          {member?.imageUrl ? (
                            <img
                              src={member.imageUrl}
                              alt={member.name}
                              className="w-8 h-8 rounded-full object-cover"
                            />
                          ) : (
                            <div className="w-8 h-8 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary text-sm font-medium">
                              {getMemberName(payment.memberId).split(' ').map(n => n[0]).join('')}
                            </div>
                          )}
                          <div>
                            <p className="font-medium">{getMemberName(payment.memberId)}</p>
                            {member && (
                              <p className="text-xs text-muted-foreground">{member.email}</p>
                            )}
                          </div>
                        </div>
                      </td>
                      <td className="p-4 border-b">
                        <div className="font-medium">
                          {new Date(payment.date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                          })}
                        </div>
                        <div className="text-xs text-muted-foreground">
                          {new Date(payment.date).toLocaleDateString('en-US', { weekday: 'long' })}
                        </div>
                      </td>
                      <td className="p-4 border-b">
                        <span className="inline-block px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                          {payment.type}
                        </span>
                      </td>
                      <td className="p-4 border-b">
                        <span className="inline-flex items-center gap-1 px-2 py-1 bg-secondary/50 text-secondary-foreground rounded text-sm">
                          {payment.paymentMethod === 'Cash' && '💵'}
                          {payment.paymentMethod === 'Mobile Money' && '📱'}
                          {payment.paymentMethod === 'Bank Transfer' && '🏦'}
                          {payment.paymentMethod === 'Check' && '📄'}
                          {payment.paymentMethod === 'Card' && '💳'}
                          {payment.paymentMethod === 'Other' && '🔄'}
                          {payment.paymentMethod}
                        </span>
                      </td>
                      <td className="p-4 border-b text-right">
                        <span className="font-bold text-lg">₵{payment.amount.toLocaleString()}</span>
                      </td>
                      <td className="p-4 border-b">
                        <span className="text-sm text-muted-foreground text-sm">
                          {payment.description || '—'}
                        </span>
                      </td>
                      <td className="p-4 border-b text-center">
                        <div className="flex items-center justify-center space-x-2">
                          <button 
                            onClick={() => onEditPayment(payment)}
                            className="p-2 text-muted-foreground hover:text-primary hover:bg-accent rounded-lg transition-colors"
                            title="Edit Payment"
                          >
                            ✏️
                          </button>
                          <button 
                            onClick={() => onDeletePayment(payment.id)}
                            className="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
                            title="Delete Payment"
                          >
                            🗑
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center py-12">
            <div className="text-6xl mb-4">💰</div>
            <h3 className="text-lg mb-2">No payments found</h3>
            <p className="text-muted-foreground mb-4">
              {searchTerm || typeFilter !== 'all' 
                ? 'Try adjusting your search criteria' 
                : 'Add your first payment to get started'}
            </p>
            {(!searchTerm && typeFilter === 'all') && (
              <button 
                onClick={onAddPayment}
                className="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors"
              >
                Add First Payment
              </button>
            )}
          </div>
        )}

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="p-6 border-t">
            <div className="flex items-center justify-between">
              <div className="text-sm text-muted-foreground">
                Showing {startIndex + 1} to {Math.min(endIndex, filteredPayments.length)} of {filteredPayments.length} payments
              </div>
              <div className="flex items-center space-x-2">
                {renderPaginationButtons()}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

// Member-specific Monthly Tracker Component
const MemberMonthlyTracker: React.FC<{
  member: Member
  payments: Payment[]
  members: Member[]
}> = ({ member, payments, members }) => {
  const [selectedMonth, setSelectedMonth] = useState(() => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  })
  
  // Filter payments for this specific member
  const memberPayments = payments.filter(p => p.memberId === member.id)
  
  // Filter payments by selected month
  const monthlyPayments = memberPayments.filter(payment => {
    const paymentDate = new Date(payment.date)
    const paymentMonth = `${paymentDate.getFullYear()}-${String(paymentDate.getMonth() + 1).padStart(2, '0')}`
    return paymentMonth === selectedMonth
  })
  
  // Group member's payments by month for trend analysis
  const memberMonthlyTrends = memberPayments.reduce((acc, payment) => {
    const paymentDate = new Date(payment.date)
    const monthKey = `${paymentDate.getFullYear()}-${String(paymentDate.getMonth() + 1).padStart(2, '0')}`
    if (!acc[monthKey]) {
      acc[monthKey] = { count: 0, total: 0, payments: [] }
    }
    acc[monthKey].count++
    acc[monthKey].total += payment.amount
    acc[monthKey].payments.push(payment)
    return acc
  }, {} as Record<string, { count: number, total: number, payments: Payment[] }>)

  const totalMonthlyAmount = monthlyPayments.reduce((sum, p) => sum + p.amount, 0)
  const [year, month] = selectedMonth.split('-')
  const monthName = new Date(parseInt(year), parseInt(month) - 1).toLocaleDateString('en-US', { 
    month: 'long', 
    year: 'numeric' 
  })
  
  // Get recent 6 months for comparison
  const recentMonths = Object.keys(memberMonthlyTrends)
    .sort()
    .slice(-6)
    .map(monthKey => {
      const [y, m] = monthKey.split('-')
      const monthData = memberMonthlyTrends[monthKey]
      return {
        month: new Date(parseInt(y), parseInt(m) - 1).toLocaleDateString('en-US', { 
          month: 'short', 
          year: '2-digit' 
        }),
        ...monthData,
        monthKey
      }
    })

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h3>{member.name}'s Monthly Payment Tracker</h3>
          <p className="text-muted-foreground">Track monthly payment patterns and trends</p>
        </div>
        
        <div className="flex items-center gap-2">
          <label htmlFor="monthSelect" className="text-sm text-muted-foreground">Month:</label>
          <input
            id="monthSelect"
            type="month"
            value={selectedMonth}
            onChange={(e) => setSelectedMonth(e.target.value)}
            className="px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
          />
        </div>
      </div>

      {/* Payment Trends */}
      {recentMonths.length > 1 && (
        <div className="bg-card rounded-lg border shadow-sm p-6">
          <h4 className="mb-4">6-Month Payment Trend</h4>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {recentMonths.map((monthData) => (
              <div 
                key={monthData.monthKey}
                className={`p-4 rounded-lg border text-center transition-all cursor-pointer hover:shadow-md ${
                  monthData.monthKey === selectedMonth 
                    ? 'bg-primary/10 border-primary shadow-md' 
                    : 'bg-muted/20 hover:bg-muted/30'
                }`}
                onClick={() => setSelectedMonth(monthData.monthKey)}
              >
                <p className="text-sm font-medium">{monthData.month}</p>
                <p className="text-xl font-bold">{monthData.total > 0 ? `₵${monthData.total.toLocaleString()}` : '₵0'}</p>
                <p className="text-xs text-muted-foreground">{monthData.count} payments</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Monthly Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-card rounded-lg border p-6 shadow-sm">
          <h4 className="text-sm text-muted-foreground mb-2">Payments in {monthName}</h4>
          <div className="text-2xl mb-1">{monthlyPayments.length}</div>
          <p className="text-xs text-muted-foreground">Transactions</p>
        </div>
        <div className="bg-card rounded-lg border p-6 shadow-sm">
          <h4 className="text-sm text-muted-foreground mb-2">Total Amount</h4>
          <div className="text-2xl mb-1">₵{totalMonthlyAmount.toLocaleString()}</div>
          <p className="text-xs text-muted-foreground">Total contributions</p>
        </div>
        <div className="bg-card rounded-lg border p-6 shadow-sm">
          <h4 className="text-sm text-muted-foreground mb-2">Average Payment</h4>
          <div className="text-2xl mb-1">
            ₵{monthlyPayments.length > 0 ? (totalMonthlyAmount / monthlyPayments.length).toFixed(0) : '0'}
          </div>
          <p className="text-xs text-muted-foreground">Per transaction</p>
        </div>
        <div className="bg-card rounded-lg border p-6 shadow-sm">
          <h4 className="text-sm text-muted-foreground mb-2">Payment Types</h4>
          <div className="text-2xl mb-1">{[...new Set(monthlyPayments.map(p => p.type))].length}</div>
          <p className="text-xs text-muted-foreground">Different types</p>
        </div>
      </div>

      {/* Payment Details Table */}
      {monthlyPayments.length > 0 ? (
        <div className="bg-card rounded-lg border shadow-sm">
          <div className="p-6 border-b">
            <h4>Payments in {monthName}</h4>
            <p className="text-sm text-muted-foreground">
              {monthlyPayments.length} payments totaling ₵{totalMonthlyAmount.toLocaleString()}
            </p>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-muted/50">
                <tr>
                  <th className="text-left p-4 border-b font-medium">Date</th>
                  <th className="text-left p-4 border-b font-medium">Type</th>
                  <th className="text-left p-4 border-b font-medium">Method</th>
                  <th className="text-right p-4 border-b font-medium">Amount</th>
                  <th className="text-left p-4 border-b font-medium">Description</th>
                </tr>
              </thead>
              <tbody>
                {monthlyPayments
                  .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
                  .map((payment, index) => (
                  <tr 
                    key={payment.id}
                    className={`hover:bg-accent/50 transition-colors ${
                      index % 2 === 0 ? 'bg-background' : 'bg-muted/20'
                    }`}
                  >
                    <td className="p-4 border-b">
                      <div className="font-medium">
                        {new Date(payment.date).toLocaleDateString('en-US', {
                          month: 'short',
                          day: 'numeric',
                          year: 'numeric'
                        })}
                      </div>
                      <div className="text-xs text-muted-foreground">
                        {new Date(payment.date).toLocaleDateString('en-US', { weekday: 'long' })}
                      </div>
                    </td>
                    <td className="p-4 border-b">
                      <span className="inline-block px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                        {payment.type}
                      </span>
                    </td>
                    <td className="p-4 border-b">
                      <span className="inline-flex items-center gap-1 text-sm">
                        {payment.paymentMethod === 'Cash' && '💵'}
                        {payment.paymentMethod === 'Mobile Money' && '📱'}
                        {payment.paymentMethod === 'Bank Transfer' && '🏦'}
                        {payment.paymentMethod === 'Check' && '📄'}
                        {payment.paymentMethod === 'Card' && '💳'}
                        {payment.paymentMethod === 'Other' && '🔄'}
                        {payment.paymentMethod}
                      </span>
                    </td>
                    <td className="p-4 border-b text-right">
                      <span className="font-bold text-lg">₵{payment.amount.toLocaleString()}</span>
                    </td>
                    <td className="p-4 border-b">
                      <span className="text-sm text-muted-foreground">
                        {payment.description || '—'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-muted/30">
                <tr>
                  <td colSpan={3} className="p-4 border-t font-medium">
                    Total ({monthlyPayments.length} payments)
                  </td>
                  <td className="p-4 border-t text-right font-bold text-lg">
                    ₵{totalMonthlyAmount.toLocaleString()}
                  </td>
                  <td className="p-4 border-t"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      ) : (
        <div className="bg-card rounded-lg border shadow-sm p-12 text-center">
          <div className="text-6xl mb-4">📅</div>
          <h4 className="text-lg mb-2">No Payments in {monthName}</h4>
          <p className="text-muted-foreground">
            {member.name} made no payments during {monthName}
          </p>
        </div>
      )}
    </div>
  )
}

// Monthly Payment Tracker Component
const MonthlyPaymentTracker: React.FC<{
  payments: Payment[]
  members: Member[]
}> = ({ payments, members }) => {
  const [selectedMonth, setSelectedMonth] = useState(() => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  })
  const [selectedMemberId, setSelectedMemberId] = useState<string>('')
  
  const getMemberName = (memberId: string) => {
    const member = members.find(m => m.id === memberId)
    return member ? member.name : 'Unknown Member'
  }

  const getMember = (memberId: string) => {
    return members.find(m => m.id === memberId)
  }
  
  const selectedMember = getMember(selectedMemberId)
  
  // Filter payments by selected month and member
  const monthlyPayments = payments.filter(payment => {
    const paymentDate = new Date(payment.date)
    const paymentMonth = `${paymentDate.getFullYear()}-${String(paymentDate.getMonth() + 1).padStart(2, '0')}`
    const matchesMonth = paymentMonth === selectedMonth
    const matchesMember = !selectedMemberId || payment.memberId === selectedMemberId
    return matchesMonth && matchesMember
  })
  
  // Get member's payment history across all months for comparison
  const memberAllPayments = selectedMemberId 
    ? payments.filter(p => p.memberId === selectedMemberId)
    : []
  
  // Group member's payments by month for trend analysis
  const memberMonthlyTrends = selectedMemberId ? 
    memberAllPayments.reduce((acc, payment) => {
      const paymentDate = new Date(payment.date)
      const monthKey = `${paymentDate.getFullYear()}-${String(paymentDate.getMonth() + 1).padStart(2, '0')}`
      if (!acc[monthKey]) {
        acc[monthKey] = { count: 0, total: 0, payments: [] }
      }
      acc[monthKey].count++
      acc[monthKey].total += payment.amount
      acc[monthKey].payments.push(payment)
      return acc
    }, {} as Record<string, { count: number, total: number, payments: Payment[] }>) : {}

  const totalMonthlyAmount = monthlyPayments.reduce((sum, p) => sum + p.amount, 0)
  const [year, month] = selectedMonth.split('-')
  const monthName = new Date(parseInt(year), parseInt(month) - 1).toLocaleDateString('en-US', { 
    month: 'long', 
    year: 'numeric' 
  })
  
  // Get recent months for comparison
  const recentMonths = Object.keys(memberMonthlyTrends)
    .sort()
    .slice(-6)
    .map(monthKey => {
      const [y, m] = monthKey.split('-')
      const monthData = memberMonthlyTrends[monthKey]
      return {
        month: new Date(parseInt(y), parseInt(m) - 1).toLocaleDateString('en-US', { 
          month: 'short', 
          year: '2-digit' 
        }),
        ...monthData,
        monthKey
      }
    })

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h2>Member Monthly Payment Tracker</h2>
          <p className="text-muted-foreground">Track individual member payments by month</p>
        </div>
        
        <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <div className="flex items-center gap-2">
            <label htmlFor="monthSelect" className="text-sm text-muted-foreground">Month:</label>
            <input
              id="monthSelect"
              type="month"
              value={selectedMonth}
              onChange={(e) => setSelectedMonth(e.target.value)}
              className="px-3 py-2 bg-input-background border rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
            />
          </div>
        </div>
      </div>

      {/* Member Selection */}
      <div className="bg-card rounded-lg border shadow-sm p-6">
        <h3 className="mb-4">Select Member to Track</h3>
        <div className="space-y-3">
          <MemberSearchSelector
            members={members}
            selectedMemberId={selectedMemberId}
            onMemberSelect={setSelectedMemberId}
          />
          
          {selectedMember && (
            <div className="flex items-center space-x-3 p-4 bg-muted/30 rounded-lg">
              {selectedMember.imageUrl ? (
                <img
                  src={selectedMember.imageUrl}
                  alt={selectedMember.name}
                  className="w-12 h-12 rounded-full object-cover"
                />
              ) : (
                <div className="w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/40 rounded-full flex items-center justify-center text-primary font-medium">
                  {selectedMember.name.split(' ').map(n => n[0]).join('')}
                </div>
              )}
              <div className="flex-1">
                <p className="font-medium">{selectedMember.name}</p>
                <p className="text-sm text-muted-foreground">{selectedMember.profession} • {selectedMember.email}</p>
                <p className="text-sm text-muted-foreground">Member since {new Date(selectedMember.membershipDate).toLocaleDateString()}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      {selectedMember ? (
        <>
          {/* Member Payment Trends */}
          {recentMonths.length > 1 && (
            <div className="bg-card rounded-lg border shadow-sm p-6">
              <h3 className="mb-4">Payment History Trend for {selectedMember.name}</h3>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                {recentMonths.map((monthData, index) => (
                  <div 
                    key={monthData.monthKey}
                    className={`p-4 rounded-lg border text-center ${
                      monthData.monthKey === selectedMonth 
                        ? 'bg-primary/10 border-primary' 
                        : 'bg-muted/20'
                    }`}
                  >
                    <p className="text-sm font-medium">{monthData.month}</p>
                    <p className="text-lg font-bold">₵{monthData.total.toLocaleString()}</p>
                    <p className="text-xs text-muted-foreground">{monthData.count} payments</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </>
      ) : (
        <div className="bg-card rounded-lg border shadow-sm p-12 text-center">
          <div className="text-6xl mb-4">👤</div>
          <h3 className="text-lg mb-2">Select a Member</h3>
          <p className="text-muted-foreground">
            Choose a member from the search above to view their monthly payment tracking
          </p>
        </div>
      )}

      {selectedMember && (
        <>
          {/* Monthly Summary Cards for Selected Member */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="bg-card rounded-lg border p-6 shadow-sm">
              <h3 className="text-sm text-muted-foreground mb-2">Payments in {monthName}</h3>
              <div className="text-2xl mb-1">{monthlyPayments.length}</div>
              <p className="text-xs text-muted-foreground">Transactions by {selectedMember.name}</p>
            </div>
            <div className="bg-card rounded-lg border p-6 shadow-sm">
              <h3 className="text-sm text-muted-foreground mb-2">Total Amount</h3>
              <div className="text-2xl mb-1">₵{totalMonthlyAmount.toLocaleString()}</div>
              <p className="text-xs text-muted-foreground">Paid in {monthName}</p>
            </div>
            <div className="bg-card rounded-lg border p-6 shadow-sm">
              <h3 className="text-sm text-muted-foreground mb-2">Average Payment</h3>
              <div className="text-2xl mb-1">
                ₵{monthlyPayments.length > 0 ? (totalMonthlyAmount / monthlyPayments.length).toFixed(0) : '0'}
              </div>
              <p className="text-xs text-muted-foreground">Per transaction</p>
            </div>
            <div className="bg-card rounded-lg border p-6 shadow-sm">
              <h3 className="text-sm text-muted-foreground mb-2">Payment Types</h3>
              <div className="text-2xl mb-1">{[...new Set(monthlyPayments.map(p => p.type))].length}</div>
              <p className="text-xs text-muted-foreground">Different types used</p>
            </div>
          </div>

          {/* Payment Details for Selected Member and Month */}
          {monthlyPayments.length > 0 ? (
            <div className="bg-card rounded-lg border shadow-sm">
              <div className="p-6 border-b">
                <h3>{selectedMember.name}'s Payments in {monthName}</h3>
                <p className="text-sm text-muted-foreground">
                  {monthlyPayments.length} payments totaling ₵{totalMonthlyAmount.toLocaleString()}
                </p>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead className="bg-muted/50">
                    <tr>
                      <th className="text-left p-4 border-b font-medium">Date</th>
                      <th className="text-left p-4 border-b font-medium">Payment Type</th>
                      <th className="text-left p-4 border-b font-medium">Method</th>
                      <th className="text-right p-4 border-b font-medium">Amount</th>
                      <th className="text-left p-4 border-b font-medium">Description</th>
                    </tr>
                  </thead>
                  <tbody>
                    {monthlyPayments
                      .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
                      .map((payment, index) => (
                      <tr 
                        key={payment.id}
                        className={`hover:bg-accent/50 transition-colors ${
                          index % 2 === 0 ? 'bg-background' : 'bg-muted/20'
                        }`}
                      >
                        <td className="p-4 border-b">
                          <div className="font-medium">
                            {new Date(payment.date).toLocaleDateString('en-US', {
                              month: 'short',
                              day: 'numeric',
                              year: 'numeric'
                            })}
                          </div>
                          <div className="text-xs text-muted-foreground">
                            {new Date(payment.date).toLocaleDateString('en-US', { weekday: 'long' })}
                          </div>
                        </td>
                        <td className="p-4 border-b">
                          <span className="inline-block px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                            {payment.type}
                          </span>
                        </td>
                        <td className="p-4 border-b">
                          <span className="inline-flex items-center gap-1 text-sm">
                            {payment.paymentMethod === 'Cash' && '💵'}
                            {payment.paymentMethod === 'Mobile Money' && '📱'}
                            {payment.paymentMethod === 'Bank Transfer' && '🏦'}
                            {payment.paymentMethod === 'Check' && '📄'}
                            {payment.paymentMethod === 'Card' && '💳'}
                            {payment.paymentMethod === 'Other' && '🔄'}
                            {payment.paymentMethod}
                          </span>
                        </td>
                        <td className="p-4 border-b text-right">
                          <span className="font-bold text-lg">₵{payment.amount.toLocaleString()}</span>
                        </td>
                        <td className="p-4 border-b">
                          <span className="text-sm text-muted-foreground">
                            {payment.description || '—'}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot className="bg-muted/30">
                    <tr>
                      <td colSpan={3} className="p-4 border-t font-medium">
                        Total ({monthlyPayments.length} payments)
                      </td>
                      <td className="p-4 border-t text-right font-bold text-lg">
                        ₵{totalMonthlyAmount.toLocaleString()}
                      </td>
                      <td className="p-4 border-t"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          ) : (
            <div className="bg-card rounded-lg border shadow-sm p-12 text-center">
              <div className="text-6xl mb-4">📅</div>
              <h3 className="text-lg mb-2">No Payments in {monthName}</h3>
              <p className="text-muted-foreground mb-4">
                {selectedMember.name} made no payments during {monthName}
              </p>
            </div>
          )}
        </>
      )}


    </div>
  )
}

// Theme types and configurations
type Theme = 'light' | 'dark' | 'blue' | 'purple' | 'green' | 'orange'

const themes: Record<Theme, { name: string; icon: string }> = {
  light: { name: 'Light', icon: '☀️' },
  dark: { name: 'Dark', icon: '🌙' },
  blue: { name: 'Ocean Blue', icon: '🌊' },
  purple: { name: 'Royal Purple', icon: '💜' },
  green: { name: 'Forest Green', icon: '🌿' },
  orange: { name: 'Sunset Orange', icon: '🧡' }
}

// Theme Context
const ThemeContext = React.createContext<{
  theme: Theme
  setTheme: (theme: Theme) => void
}>({
  theme: 'light',
  setTheme: () => {}
})

// Theme Provider Component
const ThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [theme, setTheme] = useState<Theme>('light')

  React.useEffect(() => {
    const savedTheme = localStorage.getItem('church-theme') as Theme
    if (savedTheme && themes[savedTheme]) {
      setTheme(savedTheme)
    }
  }, [])

  React.useEffect(() => {
    const root = document.documentElement
    
    // Remove all theme classes first
    root.classList.remove('light', 'dark', 'theme-blue', 'theme-purple', 'theme-green', 'theme-orange')
    
    // Add the current theme class
    if (theme === 'dark') {
      root.classList.add('dark')
    } else if (theme !== 'light') {
      root.classList.add(`theme-${theme}`)
    }
    
    localStorage.setItem('church-theme', theme)
  }, [theme])

  return (
    <ThemeContext.Provider value={{ theme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  )
}

// Theme Switcher Component
const ThemeSwitcher: React.FC = () => {
  const { theme, setTheme } = React.useContext(ThemeContext)
  const [isOpen, setIsOpen] = useState(false)

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent transition-colors"
        title="Change Theme"
      >
        <span>{themes[theme].icon}</span>
        <span className="hidden sm:inline">{themes[theme].name}</span>
      </button>

      {isOpen && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setIsOpen(false)} />
          <div className="absolute right-0 mt-2 w-48 bg-card border rounded-lg shadow-lg z-20">
            <div className="p-2">
              <div className="text-sm font-medium p-2 text-muted-foreground">Choose Theme</div>
              {Object.entries(themes).map(([themeKey, themeData]) => (
                <button
                  key={themeKey}
                  onClick={() => {
                    setTheme(themeKey as Theme)
                    setIsOpen(false)
                  }}
                  className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
                    theme === themeKey 
                      ? 'bg-primary text-primary-foreground' 
                      : 'hover:bg-accent'
                  }`}
                >
                  <span>{themeData.icon}</span>
                  <span>{themeData.name}</span>
                  {theme === themeKey && <span className="ml-auto">✓</span>}
                </button>
              ))}
            </div>
          </div>
        </>
      )}
    </div>
  )
}

// Login Component
const Login: React.FC<{
  onLogin: (credentials: { username: string; password: string }) => void
}> = ({ onLogin }) => {
  const [formData, setFormData] = useState({ username: '', password: '' })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)
    setError('')

    // Simulate API call
    setTimeout(() => {
      if (formData.username === 'admin' && formData.password === 'password') {
        onLogin(formData)
      } else {
        setError('Invalid username or password')
      }
      setIsLoading(false)
    }, 1000)
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary/5 via-background to-primary/10 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="bg-card rounded-2xl border shadow-2xl hover:shadow-3xl transition-shadow duration-300 overflow-hidden backdrop-blur-sm">
          {/* Header */}
          <div className="bg-gradient-to-r from-primary to-primary/80 p-8 text-center">
            <div className="w-20 h-20 bg-white/20 rounded-full mx-auto mb-4 flex items-center justify-center">
              <span className="text-3xl">⛪</span>
            </div>
            <h1 className="text-2xl font-bold text-primary-foreground mb-2">
              Grace Community Church
            </h1>
            <p className="text-primary-foreground/80">
              Church Management System
            </p>
          </div>

          {/* Login Form */}
          <div className="p-8">
            <div className="text-center mb-6">
              <h2 className="text-xl mb-2">Welcome Back</h2>
              <p className="text-muted-foreground">Please sign in to continue</p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="space-y-1">
                <label htmlFor="username" className="block text-sm font-medium text-muted-foreground mb-2">
                  Username
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg className="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <input
                    id="username"
                    type="text"
                    value={formData.username}
                    onChange={(e) => setFormData(prev => ({ ...prev, username: e.target.value }))}
                    required
                    className="w-full pl-10 pr-4 py-3 bg-input-background border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 hover:border-primary/50"
                    placeholder="Enter your username"
                  />
                </div>
              </div>

              <div className="space-y-1">
                <label htmlFor="password" className="block text-sm font-medium text-muted-foreground mb-2">
                  Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg className="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <input
                    id="password"
                    type="password"
                    value={formData.password}
                    onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
                    required
                    className="w-full pl-10 pr-4 py-3 bg-input-background border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 hover:border-primary/50"
                    placeholder="Enter your password"
                  />
                </div>
              </div>

              {error && (
                <div className="bg-destructive/10 border border-destructive/20 text-destructive p-4 rounded-lg text-sm flex items-center gap-3">
                  <svg className="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{error}</span>
                </div>
              )}

              <button
                type="submit"
                disabled={isLoading}
                className="group relative w-full bg-gradient-to-r from-primary to-primary/80 text-primary-foreground py-3.5 rounded-xl hover:from-primary/90 hover:to-primary/70 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0 focus:ring-4 focus:ring-primary/30 focus:outline-none"
              >
                <div className="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <div className="relative flex items-center justify-center gap-3">
                  {isLoading ? (
                    <>
                      <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                      <span>Signing in...</span>
                    </>
                  ) : (
                    <>
                      <svg 
                        className="w-5 h-5 transition-transform group-hover:scale-110" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path 
                          strokeLinecap="round" 
                          strokeLinejoin="round" 
                          strokeWidth={2} 
                          d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" 
                        />
                      </svg>
                      <span>Sign In</span>
                      <svg 
                        className="w-4 h-4 transition-transform group-hover:translate-x-1" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path 
                          strokeLinecap="round" 
                          strokeLinejoin="round" 
                          strokeWidth={2} 
                          d="M9 5l7 7-7 7" 
                        />
                      </svg>
                    </>
                  )}
                </div>
              </button>
            </form>

            <div className="mt-8 p-4 bg-muted/30 rounded-lg border">
              <div className="text-center">
                <p className="text-sm font-medium text-muted-foreground mb-3">Demo Credentials</p>
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div className="bg-background/50 rounded-lg p-3 border">
                    <p className="text-muted-foreground">Username</p>
                    <p className="font-mono font-medium text-foreground">admin</p>
                  </div>
                  <div className="bg-background/50 rounded-lg p-3 border">
                    <p className="text-muted-foreground">Password</p>
                    <p className="font-mono font-medium text-foreground">password</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Theme Switcher at Bottom */}
        <div className="mt-6 flex justify-center">
          <ThemeSwitcher />
        </div>
      </div>
    </div>
  )
}

// Navigation Sidebar Component
const Sidebar: React.FC<{
  currentPage: string
  onPageChange: (page: string) => void
  onLogout: () => void
}> = ({ currentPage, onPageChange, onLogout }) => {
  const navItems = [
    { id: 'dashboard', label: 'Dashboard', icon: '📊' },
    { id: 'members', label: 'Members', icon: '👥' },
    { id: 'payments', label: 'Payments', icon: '💰' },
    { id: 'monthly', label: 'Monthly Tracker', icon: '📅' },
    { id: 'settings', label: 'Settings', icon: '⚙️' },
  ]

  return (
    <aside className="w-64 bg-card shadow-sm h-[calc(100vh-80px)] border-r flex flex-col">
      <nav className="p-4 space-y-2 flex-1">
        {navItems.map((item) => (
          <button
            key={item.id}
            onClick={() => onPageChange(item.id)}
            className={`w-full flex items-center px-3 py-2 rounded-lg transition-colors ${
              currentPage === item.id 
                ? 'bg-primary text-primary-foreground' 
                : 'text-foreground hover:bg-accent'
            }`}
          >
            <span className="mr-3">{item.icon}</span>
            {item.label}
          </button>
        ))}
      </nav>
      
      {/* Bottom section with logout */}
      <div className="p-4 border-t">
        <button
          onClick={onLogout}
          className="w-full flex items-center px-3 py-2 rounded-lg transition-colors text-destructive hover:bg-destructive/10"
        >
          <span className="mr-3">🚪</span>
          Logout
        </button>
      </div>
    </aside>
  )
}

// Main App Content Component
const AppContent: React.FC = () => {
  const [currentPage, setCurrentPage] = useState('dashboard')
  const [currentView, setCurrentView] = useState<'list' | 'detail' | 'form'>('list')
  const [selectedMember, setSelectedMember] = useState<Member | null>(null)
  const [editingMember, setEditingMember] = useState<Member | null>(null)
  const [editingPayment, setEditingPayment] = useState<Payment | null>(null)
  const [addingPaymentForMember, setAddingPaymentForMember] = useState<string | null>(null)
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const [user, setUser] = useState<{ username: string } | null>(null)

  // Sample data
  const [members, setMembers] = useState<Member[]>([
    {
      id: '1',
      name: 'John Smith',
      email: 'john.smith@email.com',
      phone: '(555) 123-4567',
      profession: 'Teacher',
      digitalAddress: 'GA-123-4567',
      houseAddress: '123 Main St, East Legon',
      membershipDate: '2020-01-15T00:00:00.000Z',
      status: 'active'
    },
    {
      id: '2',
      name: 'Mary Johnson',
      email: 'mary.johnson@email.com',
      phone: '(555) 987-6543',
      profession: 'Nurse',
      digitalAddress: 'GA-456-7890',
      houseAddress: '456 Oak Ave, Adenta',
      membershipDate: '2019-06-20T00:00:00.000Z',
      status: 'active'
    },
    {
      id: '3',
      name: 'David Wilson',
      email: 'david.wilson@email.com',
      phone: '(555) 456-7890',
      profession: 'Engineer',
      digitalAddress: 'GA-789-0123',
      houseAddress: '789 Pine Rd, Tema',
      membershipDate: '2021-03-10T00:00:00.000Z',
      status: 'inactive'
    }
  ])

  const [payments, setPayments] = useState<Payment[]>([
    {
      id: '1',
      memberId: '1',
      amount: 100,
      type: 'Tithe',
      paymentMethod: 'Cash',
      date: '2024-01-07T00:00:00.000Z',
      description: 'Weekly tithe'
    },
    {
      id: '2',
      memberId: '1',
      amount: 50,
      type: 'Offering',
      paymentMethod: 'Mobile Money',
      date: '2024-01-07T00:00:00.000Z',
      description: 'Sunday offering'
    },
    {
      id: '3',
      memberId: '2',
      amount: 200,
      type: 'Building Fund',
      paymentMethod: 'Bank Transfer',
      date: '2024-01-06T00:00:00.000Z',
      description: 'Monthly building fund contribution'
    },
    {
      id: '4',
      memberId: '2',
      amount: 75,
      type: 'Tithe',
      paymentMethod: 'Cash',
      date: '2024-01-06T00:00:00.000Z',
      description: 'Weekly tithe'
    },
    {
      id: '5',
      memberId: '3',
      amount: 25,
      type: 'Dues',
      paymentMethod: 'Mobile Money',
      date: '2024-01-05T00:00:00.000Z',
      description: 'Monthly membership dues'
    }
  ])

  // Event handlers
  const handleAddMember = () => {
    setEditingMember(null)
    setCurrentView('form')
  }

  const handleEditMember = (member: Member) => {
    setEditingMember(member)
    setCurrentView('form')
  }

  const handleViewMember = (member: Member) => {
    setSelectedMember(member)
    setCurrentView('detail')
  }

  const handleDeleteMember = (memberId: string) => {
    if (confirm('Are you sure you want to delete this member?')) {
      setMembers(prev => prev.filter(m => m.id !== memberId))
      setPayments(prev => prev.filter(p => p.memberId !== memberId))
    }
  }

  const handleSubmitMember = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    
    // Extract data from the mock event structure
    const elements = (e.currentTarget as any).elements
    
    const memberData = {
      name: elements.name?.value || '',
      email: elements.email?.value || '',
      phone: elements.phone?.value || '',
      profession: elements.profession?.value || '',
      digitalAddress: elements.digitalAddress?.value || '',
      houseAddress: elements.houseAddress?.value || '',
      membershipDate: elements.membershipDate?.value || '',
      status: (elements.status?.value || 'active') as 'active' | 'inactive',
      imageUrl: elements.imageUrl?.value || undefined
    }

    if (editingMember) {
      setMembers(prev => prev.map(m => 
        m.id === editingMember.id 
          ? { ...memberData, id: editingMember.id }
          : m
      ))
    } else {
      const newMember: Member = {
        ...memberData,
        id: Date.now().toString()
      }
      setMembers(prev => [...prev, newMember])
    }
    setCurrentView('list')
    setEditingMember(null)
  }

  const handleAddPayment = (memberId?: string) => {
    setEditingPayment(null)
    setAddingPaymentForMember(memberId || null)
    if (currentPage !== 'payments') {
      setCurrentPage('payments')
    }
    setCurrentView('form')
  }

  const handleEditPayment = (payment: Payment) => {
    setEditingPayment(payment)
    setAddingPaymentForMember(null)
    setCurrentPage('payments') // Switch to payments page
    setCurrentView('form')
  }

  const handleDeletePayment = (paymentId: string) => {
    if (confirm('Are you sure you want to delete this payment?')) {
      setPayments(prev => prev.filter(p => p.id !== paymentId))
    }
  }

  const handleSubmitPayment = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    const formData = new FormData(e.currentTarget)
    const paymentData = {
      memberId: formData.get('memberId') as string || addingPaymentForMember || editingPayment?.memberId || '',
      amount: parseFloat(formData.get('amount') as string) || 0,
      type: formData.get('type') as string,
      paymentMethod: formData.get('paymentMethod') as string,
      date: formData.get('date') as string,
      description: formData.get('description') as string
    }

    if (editingPayment) {
      setPayments(prev => prev.map(p => 
        p.id === editingPayment.id 
          ? { ...paymentData, id: editingPayment.id }
          : p
      ))
    } else {
      const newPayment: Payment = {
        ...paymentData,
        id: Date.now().toString()
      }
      setPayments(prev => [...prev, newPayment])
    }
    setCurrentView('list')
    setEditingPayment(null)
    setAddingPaymentForMember(null)
  }

  const handleBackToList = () => {
    setCurrentView('list')
    setSelectedMember(null)
    setEditingMember(null)
    setEditingPayment(null)
    setAddingPaymentForMember(null)
  }

  const handleLogin = (credentials: { username: string; password: string }) => {
    // Simulate authentication
    setUser({ username: credentials.username })
    setIsAuthenticated(true)
  }

  const handleLogout = () => {
    setUser(null)
    setIsAuthenticated(false)
    setCurrentPage('dashboard')
    setCurrentView('list')
    setSelectedMember(null)
    setEditingMember(null)
    setEditingPayment(null)
    setAddingPaymentForMember(null)
  }

  const handlePageChange = (page: string) => {
    setCurrentPage(page)
    setCurrentView('list')
    setSelectedMember(null)
    setEditingMember(null)
    setEditingPayment(null)
    setAddingPaymentForMember(null)
  }

  // Check authentication status on component mount
  React.useEffect(() => {
    const savedAuth = localStorage.getItem('church-auth')
    if (savedAuth) {
      const authData = JSON.parse(savedAuth)
      setUser(authData.user)
      setIsAuthenticated(true)
    }
  }, [])

  // Save authentication status
  React.useEffect(() => {
    if (isAuthenticated && user) {
      localStorage.setItem('church-auth', JSON.stringify({ user, timestamp: Date.now() }))
    } else {
      localStorage.removeItem('church-auth')
    }
  }, [isAuthenticated, user])

  // Show login screen if not authenticated
  if (!isAuthenticated) {
    return <Login onLogin={handleLogin} />
  }

  // Render content based on current page and view
  const renderContent = () => {
    if (currentPage === 'dashboard') {
      return <Dashboard members={members} payments={payments} />
    }

    if (currentPage === 'members') {
      if (currentView === 'form') {
        return (
          <MemberForm
            member={editingMember || undefined}
            onBack={handleBackToList}
            onSubmit={handleSubmitMember}
          />
        )
      }
      if (currentView === 'detail' && selectedMember) {
        return (
          <MemberDetail
            member={selectedMember}
            payments={payments}
            members={members}
            onBack={handleBackToList}
            onAddPayment={handleAddPayment}
            onEditPayment={handleEditPayment}
            onDeletePayment={handleDeletePayment}
          />
        )
      }
      return (
        <MembersList
          members={members}
          payments={payments}
          onAddMember={handleAddMember}
          onViewMember={handleViewMember}
          onEditMember={handleEditMember}
          onDeleteMember={handleDeleteMember}
        />
      )
    }

    if (currentPage === 'payments') {
      if (currentView === 'form') {
        const memberForPayment = addingPaymentForMember 
          ? members.find(m => m.id === addingPaymentForMember)
          : editingPayment 
            ? members.find(m => m.id === editingPayment.memberId)
            : undefined

        return (
          <PaymentForm
            payment={editingPayment || undefined}
            members={members}
            preSelectedMemberId={addingPaymentForMember || undefined}
            onBack={handleBackToList}
            onSubmit={handleSubmitPayment}
          />
        )
      }
      return (
        <PaymentsList
          payments={payments}
          members={members}
          onAddPayment={() => handleAddPayment()}
          onEditPayment={handleEditPayment}
          onDeletePayment={handleDeletePayment}
        />
      )
    }

    if (currentPage === 'monthly') {
      return <MonthlyPaymentTracker payments={payments} members={members} />
    }

    return (
      <div className="text-center py-8 text-muted-foreground">
        Settings page coming soon...
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="bg-card shadow-sm border-b">
        <div className="px-6 py-4 flex items-center justify-between">
          <div>
            <h1>Grace Community Church</h1>
            <p className="text-muted-foreground">Church Management System</p>
          </div>
          <div className="flex items-center gap-4">
            <div className="text-right text-sm">
              <p className="font-medium">Welcome, {user?.username}</p>
              <p className="text-muted-foreground">Church Administrator</p>
            </div>
            <ThemeSwitcher />
          </div>
        </div>
      </header>

      <div className="flex">
        <Sidebar 
          currentPage={currentPage} 
          onPageChange={handlePageChange}
          onLogout={handleLogout}
        />
        
        {/* Main Content */}
        <main className="flex-1 p-6">
          {renderContent()}
        </main>
      </div>
    </div>
  )
}

// Main App Component with Theme Provider
export default function App() {
  return (
    <ThemeProvider>
      <AppContent />
    </ThemeProvider>
  )
}