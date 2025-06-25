$(document).ready(function() {
    $('#confirmationModal').hide();

    let loggedInUser = null;
    let categoryPieChartInstance;
    let expenseLineChartInstance;
    let resolveModalPromise;

    function checkLoginStatus() {
        const user = localStorage.getItem('loggedInUser');
        if (user) {
            loggedInUser = JSON.parse(user);
            $('#loggedInUsername').text(loggedInUser.username);
            fetchAndDisplayExpenses();
            const showWelcome = sessionStorage.getItem('showWelcomeToast');
            if (showWelcome === 'true') {
                showToast('Welcome back, ' + loggedInUser.username + '!');
                sessionStorage.removeItem('showWelcomeToast');
            }
        } else {
            window.location.href = 'login.html';
        }
    }

    $('#logoutButton').on('click', function() {
        localStorage.removeItem('loggedInUser');
        sessionStorage.removeItem('showWelcomeToast');
        $.ajax({
            url: 'api/logout.php',
            method: 'POST',
            success: function() {
                window.location.href = 'login.html';
            },
            error: function() {
                window.location.href = 'login.html';
            }
        });
    });

    function displayMessage(message, type) {
        const messageDiv = $('#formMessage');
        messageDiv.text(message);
        messageDiv.removeClass('success error').addClass(type);
        messageDiv.fadeIn().delay(3000).fadeOut();
    }

    function showToast(message) {
        const toastDiv = $('#welcomeToast');
        toastDiv.text(message);
        toastDiv.addClass('show');
        setTimeout(function(){
            toastDiv.removeClass('show');
        }, 3000);
    }

    function showConfirmationModal(message) {
        return new Promise((resolve) => {
            resolveModalPromise = resolve;
            $('#modalMessage').text(message);
            $('#confirmationModal').css('display', 'flex');
            $('#confirmYes').off('click').on('click', function() {
                $('#confirmationModal').hide();
                resolveModalPromise(true);
            });
            $('#confirmNo').off('click').on('click', function() {
                $('#confirmationModal').hide();
                resolveModalPromise(false);
            });
            $('.close-button').off('click').on('click', function() {
                $('#confirmationModal').hide();
                resolveModalPromise(false);
            });
            $(window).off('click.modalClose').on('click.modalClose', function(event) {
                if ($(event.target).is('#confirmationModal')) {
                    $('#confirmationModal').hide();
                    resolveModalPromise(false);
                    $(window).off('click.modalClose');
                }
            });
        });
    }

    function fetchAndDisplayExpenses() {
        if (!loggedInUser || !loggedInUser.id) {
            window.location.href = 'login.html';
            return;
        }

        const filterCategory = $('#filterCategory').val();
        const filterType = $('#filterType').val();
        const filterStartDate = $('#filterStartDate').val();
        const filterEndDate = $('#filterEndDate').val();

        const filterData = {
            user_id: loggedInUser.id,
            category: filterCategory,
            type: filterType,
            startDate: filterStartDate,
            endDate: filterEndDate
        };

        $.ajax({
            url: 'api/read_expenses.php',
            method: 'GET',
            dataType: 'json',
            data: filterData,
            success: function(response) {
                if (response.success) {
                    const transactions = response.data;
                    const tbody = $('#transactionsTable tbody');
                    tbody.empty();

                    let totalIncome = 0;
                    let totalExpenses = 0;

                    if (transactions.length === 0) {
                        tbody.append('<tr><td colspan="6" style="text-align: center; padding: 20px;">No transactions found for the selected filters.</td></tr>');
                    } else {
                        transactions.forEach(function(transaction) {
                            const isIncome = transaction.category === 'Salary';
                            const rowClass = isIncome ? 'income-text' : 'expense-text';
                            const typeText = isIncome ? 'Income' : 'Expense';
                            const amountDisplay = parseFloat(transaction.amount).toFixed(2);

                            if (isIncome) {
                                totalIncome += parseFloat(transaction.amount);
                            } else {
                                totalExpenses += parseFloat(transaction.amount);
                            }

                            const row = `
                                <tr data-id="${transaction.id}">
                                    <td>${transaction.date}</td>
                                    <td>
                                        <span class="view-mode description">${transaction.description}</span>
                                        <input type="text" class="edit-mode edit-description" value="${transaction.description}" style="display:none;">
                                    </td>
                                    <td>
                                        <span class="view-mode category">${transaction.category}</span>
                                        <select class="edit-mode edit-category" style="display:none;">
                                            ${getCategoryOptions(transaction.category)}
                                        </select>
                                    </td>
                                    <td>
                                        <span class="view-mode amount ${rowClass}">${amountDisplay}</span>
                                        <input type="number" step="0.01" class="edit-mode edit-amount" value="${amountDisplay}" style="display:none;">
                                    </td>
                                    <td>${typeText}</td>
                                    <td>
                                        <button class="btn btn-edit edit-btn" data-id="${transaction.id}"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-danger delete-btn" data-id="${transaction.id}"><i class="fas fa-trash-alt"></i></button>
                                        <button class="btn btn-primary save-btn" data-id="${transaction.id}" style="display:none;"><i class="fas fa-save"></i> Save</button>
                                        <button class="btn btn-secondary cancel-btn" data-id="${transaction.id}" style="display:none;"><i class="fas fa-times"></i> Cancel</button>
                                    </td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    }

                    $('#totalIncome').text(totalIncome.toFixed(2));
                    $('#totalExpenses').text(totalExpenses.toFixed(2));
                    const netBalance = totalIncome - totalExpenses;
                    $('#netBalance').text(netBalance.toFixed(2));

                    const balanceCard = $('.balance-card p');
                    balanceCard.removeClass('positive negative zero');

                    if (netBalance > 0) {
                        balanceCard.addClass('positive');
                    } else if (netBalance < 0) {
                        balanceCard.addClass('negative');
                    } else {
                        balanceCard.addClass('zero');
                    }

                    updateCharts(transactions);
                } else {
                    displayMessage('Error loading transactions: ' + response.message, 'error');
                    console.error('Error fetching transactions:', response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                displayMessage('Network error or server issue while loading data.', 'error');
                console.error('AJAX Error:', textStatus, errorThrown);
                if (jqXHR.status === 401 || jqXHR.status === 403) {
                    window.location.href = 'login.html';
                }
            }
        });
    }

    function getCategoryOptions(selectedCategory) {
        const categories = [
            "Food", "Transport", "Utilities", "Rent", "Entertainment",
            "Shopping", "Healthcare", "Education", "Salary", "Other"
        ];
        let optionsHtml = '';
        categories.forEach(cat => {
            const selected = cat === selectedCategory ? 'selected' : '';
            optionsHtml += `<option value="${cat}" ${selected}>${cat}</option>`;
        });
        return optionsHtml;
    }

    $('#expenseForm').on('submit', function(event) {
        event.preventDefault();

        if (!loggedInUser || !loggedInUser.id) {
            displayMessage('Please log in to add transactions.', 'error');
            return;
        }

        const formData = {
            user_id: loggedInUser.id,
            description: $('#description').val(),
            amount: $('#amount').val(),
            category: $('#category').val(),
            date: $('#date').val()
        };

        if (!formData.description || !formData.amount || !formData.category || !formData.date) {
            displayMessage('Please fill in all fields.', 'error');
            return;
        }
        if (isNaN(parseFloat(formData.amount)) || parseFloat(formData.amount) <= 0) {
            displayMessage('Please enter a valid positive amount.', 'error');
            return;
        }

        $.ajax({
            url: 'api/create_expense.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayMessage('Transaction added successfully!', 'success');
                    $('#expenseForm')[0].reset();
                    fetchAndDisplayExpenses();
                } else {
                    displayMessage('Error adding transaction: ' + response.message, 'error');
                    console.error('Error adding transaction:', response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                displayMessage('Network error or server issue while adding transaction.', 'error');
                console.error('AJAX Error:', textStatus, errorThrown);
                if (jqXHR.status === 401 || jqXHR.status === 403) {
                    window.location.href = 'login.html';
                }
            }
        });
    });

    $('#transactionsTable').on('click', '.delete-btn', async function() {
        const transactionId = $(this).data('id');

        if (!loggedInUser || !loggedInUser.id) {
            displayMessage('Please log in to delete transactions.', 'error');
            return;
        }

        const confirmation = await showConfirmationModal('Are you sure you want to delete this transaction?');

        if (confirmation) {
            $.ajax({
                url: 'api/delete_expense.php',
                method: 'POST',
                data: {
                    id: transactionId,
                    user_id: loggedInUser.id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayMessage('Transaction deleted successfully!', 'success');
                        fetchAndDisplayExpenses();
                    } else {
                        displayMessage('Error deleting transaction: ' + response.message, 'error');
                        console.error('Error deleting transaction:', response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    displayMessage('Network error or server issue while deleting transaction.', 'error');
                    console.error('AJAX Error:', textStatus, errorThrown);
                    if (jqXHR.status === 401 || jqXHR.status === 403) {
                        window.location.href = 'login.html';
                    }
                }
            });
        }
    });

    $('#transactionsTable').on('click', '.edit-btn', function() {
        const row = $(this).closest('tr');
        row.find('.view-mode').hide();
        row.find('.edit-mode').show();
        row.find('.edit-btn, .delete-btn').hide();
        row.find('.save-btn, .cancel-btn').show();
    });

    $('#transactionsTable').on('click', '.cancel-btn', function() {
        const row = $(this).closest('tr');
        row.find('.view-mode').show();
        row.find('.edit-mode').hide();
        row.find('.edit-btn, .delete-btn').show();
        row.find('.save-btn, .cancel-btn').hide();
        fetchAndDisplayExpenses();
    });

    $('#transactionsTable').on('click', '.save-btn', function() {
        const transactionId = $(this).data('id');
        const row = $(this).closest('tr');

        if (!loggedInUser || !loggedInUser.id) {
            displayMessage('Please log in to update transactions.', 'error');
            return;
        }

        const updatedData = {
            id: transactionId,
            user_id: loggedInUser.id,
            description: row.find('.edit-description').val(),
            amount: row.find('.edit-amount').val(),
            category: row.find('.edit-category').val(),
            date: row.find('td:first').text()
        };

        if (!updatedData.description || !updatedData.amount || !updatedData.category) {
            displayMessage('All fields are required for update.', 'error');
            return;
        }
        if (isNaN(parseFloat(updatedData.amount)) || parseFloat(updatedData.amount) <= 0) {
            displayMessage('Please enter a valid positive amount for update.', 'error');
            return;
        }

        $.ajax({
            url: 'api/update_expense.php',
            method: 'POST',
            data: updatedData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayMessage('Transaction updated successfully!', 'success');
                    fetchAndDisplayExpenses();
                } else {
                    displayMessage('Error updating transaction: ' + response.message, 'error');
                    console.error('Error updating transaction:', response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                displayMessage('Network error or server issue while updating transaction.', 'error');
                console.error('AJAX Error:', textStatus, errorThrown);
                if (jqXHR.status === 401 || jqXHR.status === 403) {
                    window.location.href = 'login.html';
                }
            }
        });
    });

    $('#filterCategory, #filterType, #filterStartDate, #filterEndDate').on('change', function() {
        fetchAndDisplayExpenses();
    });

    function updateCharts(transactions) {
        const categoryExpenses = {};
        transactions.forEach(transaction => {
            if (transaction.category !== 'Salary') {
                if (!categoryExpenses[transaction.category]) {
                    categoryExpenses[transaction.category] = 0;
                }
                categoryExpenses[transaction.category] += parseFloat(transaction.amount);
            }
        });

        const pieChartLabels = Object.keys(categoryExpenses);
        const pieChartData = Object.values(categoryExpenses);

        const pieCtx = document.getElementById('categoryPieChart').getContext('2d');

        if (categoryPieChartInstance) {
            categoryPieChartInstance.destroy();
        }

        categoryPieChartInstance = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: pieChartLabels,
                datasets: [{
                    label: 'Amount (INR)',
                    data: pieChartData,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(199, 199, 199, 0.7)',
                        'rgba(83, 109, 254, 0.7)',
                        'rgba(17, 239, 203, 0.7)'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#333'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Expenses by Category',
                        color: '#2c3e50',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        const dailyExpenses = {};
        transactions.forEach(transaction => {
            if (transaction.category !== 'Salary') {
                const date = transaction.date;
                if (!dailyExpenses[date]) {
                    dailyExpenses[date] = 0;
                }
                dailyExpenses[date] += parseFloat(transaction.amount);
            }
        });

        const lineChartLabels = Object.keys(dailyExpenses).sort();
        const lineChartData = lineChartLabels.map(date => dailyExpenses[date]);

        const lineCtx = document.getElementById('expenseLineChart').getContext('2d');

        if (expenseLineChartInstance) {
            expenseLineChartInstance.destroy();
        }

        expenseLineChartInstance = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: lineChartLabels,
                datasets: [{
                    label: 'Daily Expenses (INR)',
                    data: lineChartData,
                    borderColor: 'rgba(52, 152, 219, 1)',
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    fill: true,
                    tension: 0.2,
                    pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(52, 152, 219, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date',
                            color: '#2c3e50'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount (INR)',
                            color: '#2c3e50'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Expense Pattern Over Time',
                        color: '#2c3e50',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    checkLoginStatus();
});
