<div class="col-md-3 chat-pannel position-relative">
    <!-- AI TONE Modal -->
    <div class=" my-custom-ai-tone-modal-chat-pannel d-none" id="AiToneModal">
        <div class="card-ai-tone-modal-content">
            <div class="text-white border-0 rounded-t-lg header">
                <h5 class="" id="AiToneModalLabel"><img
                        src="https://res.cloudinary.com/da6qujoed/image/upload/v1744704440/robot_pfahvf.svg"
                        class="w-10 h-10"> &nbsp;AI Tone</h5>
                <button type="button" class="btn btn-link p-0 text-white close_icon"
                    onclick="DynamicClose('#AiToneModal')"><?php echo getCloseSquareIcon(); ?></button>
            </div>
            <div class="">
                <?php echo renderAIToneOptions(); ?>
            </div>
        </div>
    </div>
    <div class="card" style="background-color: transparent!important;">

        <div class="card-header" style="
                                            display: flex;
                                            flex-direction: column;
                                            justify-content: center;
                                            /* align-items: center; */
                                            padding: 12px 13px;
                                            gap: 10px;
                                            border-bottom: 0.5px solid;
                                                        ">
            <h5 class="mb-0">
                <?php echo getIconImage(0, 0, "2.5rem", "auto", "https://res.cloudinary.com/da6qujoed/image/upload/v1742656707/logoIcon_pspxgh.png", 0); ?>
                &nbsp; Boss<span style="font-weight: 700;">GPT</span> Assistant
            </h5>
            <button class="change_aitone_btn" onclick="DynamicOpen('#AiToneModal')">
                <img src="https://res.cloudinary.com/da6qujoed/image/upload/v1744704440/robot_pfahvf.svg"
                    style="width:2rem;">
            </button>
            <button class="btn btn-link p-0 text-white close-icon-btn position-absolute " data-bs-dismiss="modal"
                aria-label="Close" onclick="closeChatPannel()"><?php echo getCloseSquareIcon(); ?></button>

        </div>
        <div class="card-body p-0">
            <div class="chat-container">
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($projects)): ?>
                        <div class="welcome-guide">
                            <div class="message-thread" id="welcomeThread">
                                <!-- Messages will be inserted here by JavaScript -->
                            </div>
                        </div>

                        <script>
                            // Immediately invoke function to initialize welcome messages
                            (function initializeWelcomeMessages() {
                                // console.log('Initializing welcome messages...'); // Debug log

                                const welcomeThread = document.getElementById('welcomeThread');
                                const chatMessages = document.getElementById('chatMessages');

                                if (!welcomeThread || !chatMessages) {
                                    console.error('Required elements not found!');
                                    return;
                                }

                                const welcomeMessages = [
                                    {
                                        delay: 0,
                                        title: '👋 Welcome to BossGPT!',
                                        content: "I'm your AI Project Manager, ready to help you organize and manage your projects efficiently."
                                    },
                                    {
                                        delay: 2000,
                                        title: '🚀 Let\'s Get Started!',
                                        content: {
                                            text: "To begin your journey, click the \"Create New Project\" button above. Here's what I can help you with:",
                                            list: [
                                                '✨ Project planning and organization',
                                                '📋 Task management and tracking',
                                                '👥 Team collaboration',
                                                '📊 Progress monitoring'
                                            ]
                                        }
                                    },
                                    {
                                        delay: 4000,
                                        title: '💡 How I Can Help',
                                        content: {
                                            text: 'Once you create a project, I can:',
                                            list: [
                                                '🤖 Generate task suggestions based on your project needs',
                                                '📅 Help manage deadlines and priorities',
                                                '🔍 Provide insights and recommendations',
                                                '💬 Answer questions about your project anytime'
                                            ]
                                        }
                                    },
                                    {
                                        delay: 6000,
                                        title: '🎯 Next Steps',
                                        content: {
                                            text: 'To get the most out of BossGPT:',
                                            list: [
                                                'Click "Create New Project" and give your project a name',
                                                'Describe your project goals and requirements',
                                                'I\'ll help you break it down into manageable tasks',
                                                'Invite team members to collaborate'
                                            ],
                                            isOrdered: true
                                        }
                                    },
                                    {
                                        delay: 8000,
                                        title: '🌟 Ready to Begin?',
                                        content: {
                                            text: 'Create your first project and let\'s make something amazing together!',
                                            cta: true
                                        }
                                    }
                                ];

                                async function showMessage(message) {

                                    // Show loading animation first
                                    showChatLoading();

                                    // Wait for loading animation
                                    await new Promise(resolve => setTimeout(resolve, 1500));

                                    // Hide loading animation
                                    hideChatLoading();
                                    appendWelcomeLogo();
                                    // Create the message div
                                    const messageDiv = document.createElement('div');
                                    messageDiv.className = aiMessageClasses;
                                    messageDiv.style.opacity = "0";  // Start invisible
                                    messageDiv.style.transition = "opacity 0.5s ease-in-out"; // Smooth transition

                                    let content = `
                                                        <div class="ai-avatar">
                                                            <div class="chat-loading-avatar">
                                                            ${iconImage}
                                                            </div>
                                                        </div>
                                                        <div class="message ai text-center mt-3">
                                                            <h5>${message.title}</h5>`;

                                    if (typeof message.content === 'string') {
                                        content += `<p>${message.content}</p>`;
                                    } else {
                                        content += `<p>${message.content.text}</p>`;
                                        if (message.content.list) {
                                            const listType = message.content.isOrdered ? 'ol' : 'ul';
                                            content += `<${listType}>`;
                                            message.content.list.forEach(item => {
                                                content += `<li>${item}</li>`;
                                            });
                                            content += `</${listType}>`;
                                        }
                                        if (message.content.cta) {
                                            content += `
                <div class="cta-message">
                    <button class="btn btn-main-primary" onclick="openNewProjectModal()">
                        <i class="fas fa-plus-circle"></i> Create New Project
                    </button>
                </div>`;
                                        }
                                    }

                                    content += '</div>';
                                    messageDiv.innerHTML = content;
                                    welcomeThread?.appendChild(messageDiv);

                                    // Apply fade-in effect
                                    setTimeout(() => {
                                        messageDiv.style.opacity = "1";
                                    }, 100);

                                    // Scroll to bottom smoothly
                                    chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: "smooth" });
                                }


                                async function displayMessages() {
                                    for (const message of welcomeMessages) {
                                        await new Promise(resolve => setTimeout(resolve, message.delay));
                                        await showMessage(message);
                                    }
                                }

                                displayMessages().catch(error => console.error('Error displaying messages:', error));
                            })();
                        </script>
                    <?php endif; ?>
                </div>
                <div class="chat-input">
                    <?php


                    $prompts = [
                        "📑 suggest tasks for my project",
                        "🎯 Create task 'Your Task' and assign it to myself",
                        "📋 Create tasks for Your Feature",
                        "✏️ Move task #number to in_progress",
                        "👥 Assign task 'Your Task' to @name",
                        "📅 Set deadline for task #number to next Friday",
                        "📝 Update description of task 'Your Task'",
                        "🔄 Mark task #number as completed",
                        "📊 Show project progress",
                        "📑 List all tasks in current project",
                        "🔍 Show tasks assigned to me"
                    ];

                    function renderPromptButtons($prompts)
                    {
                        foreach ($prompts as $prompt) {
                            echo '<button style="border-radius: 20px!important;" class="btn btn-outline-light  prompt-btn" type="button" onclick="handlePromptClick(this)">' . $prompt . '</button>';
                        }
                    }
                    ?>

                    <!-- Prompt suggestions -->
                    <div class="prompt-suggestions">
                        <div class="nav nav-tabs border-0 flex-nowrap overflow-auto mb-0 px-0"
                            style="scrollbar-width: none; -ms-overflow-style: none;">
                            <?php renderPromptButtons($prompts); ?>
                        </div>
                    </div>


                    <form id="chatForm" class="d-flex">
                        <textarea class="form-control me-2" id="messageInput" placeholder="Type your message..."
                            rows="1"></textarea>
                        <button type="submit" id="aiSendMessageBtn"
                            class="btn btn-send-primary"><?php echo file_get_contents("assets/icons/send.svg"); ?>
                        </button>
                    </form>
                    <script>
                        // Auto-resize textarea as user types
                        const messageInput = document.getElementById('messageInput');
                        if (messageInput) {
                            messageInput.addEventListener('input', function () {
                                this.style.height = 'auto';
                                this.style.height = (this.scrollHeight) + 'px';
                            });
                            
                            // Add event listener for Enter key press
                            messageInput.addEventListener('keypress', function(event) {
                                if (event.key === 'Enter' && !event.shiftKey) {
                                    event.preventDefault();
                                    document.getElementById('chatForm').dispatchEvent(new Event('submit'));
                                }
                            });

                            // Reset height when form is submitted
                            document.getElementById('chatForm').addEventListener('submit', function () {
                                setTimeout(() => {
                                    // messageInput.style.height = 'auto';
                                }, 100);
                            });
                        }

                        function handlePromptClick(button) {
                            let promptText = button.innerText;
                            let inputField = document.getElementById("messageInput");
                            let aiSendMessageBtn = document.getElementById("aiSendMessageBtn");

                            // Set input field value
                            inputField.value = promptText;

                            // Trigger the input event to resize the textarea
                            const inputEvent = new Event('input', { bubbles: true });
                            inputField.dispatchEvent(inputEvent);

                            // Auto-submit the form
                            setTimeout(() => {
                                // aiSendMessageBtn.click();
                                // chatForm.submit();
                            }, 200); // Small delay to make it smooth
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
