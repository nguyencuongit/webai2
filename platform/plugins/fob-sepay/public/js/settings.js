$(() => {
    const container = $('.sepay-connected-profile')
    const bankSubAccount = $('#payment_sepay_bank_sub_account_id')
    const unsupportedBanks = ['TPBank', 'VPBank', 'VietinBank']

    const loadBankSubAccounts = () => {
        const bankAccountId = $('#payment_sepay_bank_account_id').val()
        const bankName = $('#payment_sepay_bank_account_id option:selected').text().split('-')[0].trim()

        if (unsupportedBanks.includes(bankName) || !bankAccountId) {
            bankSubAccount.parent().hide()
            return
        }

        $.ajax({
            url: container.data('get-bank-sub-accounts-url'),
            type: 'GET',
            data: { bank_account_id: bankAccountId },
            dataType: 'json',
            beforeSend: () => bankSubAccount.parent().hide(),
            success: (response) => {
                const accounts = response.data || {}
                let options = '<option value="">-- Select virtual account --</option>'

                Object.entries(accounts).forEach(([id, name]) => {
                    options += `<option value="${id}">${name}</option>`
                })

                bankSubAccount.html(options)

                if (Object.keys(accounts).length) {
                    bankSubAccount.parent().show()
                    bankSubAccount.val(container.data('bank-sub-account-id'))
                }
            },
        })
    }

    loadBankSubAccounts()
    $(document).on('change', '#payment_sepay_bank_account_id', loadBankSubAccounts)
})
